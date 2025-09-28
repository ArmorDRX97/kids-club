<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\TrialAttendance;
use App\Models\User;
use App\Services\ShiftManager;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $today = now()->startOfDay();
        $weekday = $today->isoWeekday();
        $now = now();

        $sections = Section::with([
            'direction',
            'room',
            'schedules',
            'enrollments' => function ($query) {
                $query->with(['child', 'package', 'schedule'])
                    ->whereHas('child', fn ($child) => $child->where('is_active', true))
                    ->latest('started_at');
            },
        ])->where('is_active', true)->orderBy('name')->get();

        $attendanceToday = Attendance::where('attended_on', $today->toDateString())
            ->whereIn('section_id', $sections->pluck('id'))
            ->get()
            ->groupBy('section_id')
            ->map(fn ($items) => $items->pluck('child_id')->unique()->values()->all());

        $trialAttendanceToday = TrialAttendance::where('attended_on', $today->toDateString())
            ->whereIn('section_id', $sections->pluck('id'))
            ->get()
            ->groupBy('section_id')
            ->map(fn ($items) => $items->pluck('child_id')->unique()->values()->all());

        $user = $request->user();
        $shift = $user ? $this->shiftManager->getActiveShift($user) : null;
        $shiftElapsed = null;
        $shiftCanStop = false;
        $shiftStopLockedUntil = null;

        if ($shift) {
            $seconds = now()->diffInSeconds($shift->started_at);
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            $sec = $seconds % 60;
            $shiftElapsed = sprintf('%02d:%02d:%02d', $hours, $minutes, $sec);

            if ($shift->auto_close_enabled) {
                $shiftCanStop = true;
            } else {
                if ($shift->scheduled_end_at && now()->lt($shift->scheduled_end_at)) {
                    $shiftStopLockedUntil = $shift->scheduled_end_at;
                } else {
                    $shiftCanStop = true;
                }
            }
        }

        $canManage = $user && $user->hasRole(User::ROLE_RECEPTIONIST);
        $shiftSetting = $canManage ? $this->shiftManager->getSetting($user) : null;
        $shiftActive = (bool) $shift;
        $shiftBlockReason = null;

        if ($canManage && ! $shiftActive) {
            $shiftBlockReason = 'Начните смену, чтобы отмечать посещения и принимать оплату.';
        } elseif (! $canManage && ! $shiftActive) {
            $shiftBlockReason = 'Рабочие действия доступны только ресепшионистам.';
        }

        $weekdayNames = [
            1 => 'Пн',
            2 => 'Вт',
            3 => 'Ср',
            4 => 'Чт',
            5 => 'Пт',
            6 => 'Сб',
            7 => 'Вс',
        ];

        $fullWeekdayNames = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье',
        ];

        $hasSearch = $q !== '';

        $sectionCards = $sections->map(function (Section $section) use ($weekday, $now, $attendanceToday, $trialAttendanceToday, $q, $hasSearch, $weekdayNames, $fullWeekdayNames, $shiftActive) {
            $todaySlots = $section->schedules
                ->filter(fn (SectionSchedule $slot) => $slot->weekday === $weekday)
                ->sortBy('starts_at');

            $activeSlot = $todaySlots->first(function (SectionSchedule $slot) use ($now) {
                $start = $slot->starts_at->copy()->setDate($now->year, $now->month, $now->day);
                $end = $slot->ends_at->copy()->setDate($now->year, $now->month, $now->day);
                return $now->between($start, $end);
            });

            $nextSlot = $todaySlots->first(function (SectionSchedule $slot) use ($now) {
                $start = $slot->starts_at->copy()->setDate($now->year, $now->month, $now->day);
                return $now->lt($start);
            });

            $attendanceList = collect($attendanceToday[$section->id] ?? []);
            $trialAttendanceList = collect($trialAttendanceToday[$section->id] ?? []);

            $enrollments = [];
            $hasMatches = false;

            foreach ($section->enrollments as $enrollment) {
                $child = $enrollment->child;
                if (! $child) {
                    continue;
                }

                $haystack = mb_strtolower($child->full_name . ' ' . ($child->parent_phone ?? '') . ' ' . ($child->child_phone ?? ''));
                $matchesQuery = $hasSearch ? str_contains($haystack, mb_strtolower($q)) : true;

                if ($hasSearch && ! $matchesQuery) {
                    continue;
                }

                $hasMatches = $hasMatches || $matchesQuery;

                $schedule = $enrollment->schedule;
                $scheduleLabel = $schedule
                    ? ($weekdayNames[$schedule->weekday] ?? $schedule->weekday) . ' ' . $schedule->starts_at->format('H:i') . ' – ' . $schedule->ends_at->format('H:i')
                    : 'Не выбрано';

                $isToday = $schedule && $schedule->weekday === $weekday;
                $slotStart = $schedule ? $schedule->starts_at->copy()->setDate($now->year, $now->month, $now->day) : null;
                $slotEnd = $schedule ? $schedule->ends_at->copy()->setDate($now->year, $now->month, $now->day) : null;
                $slotActive = $slotStart && $slotEnd && $now->between($slotStart, $slotEnd);
                $nextStartLabel = $slotStart && $slotStart->isFuture() ? $slotStart->format('H:i') : null;

                $alreadyAttended = $attendanceList->contains($child->id);
                $alreadyTrialAttended = $trialAttendanceList->contains($child->id);
                $needsPayment = ($enrollment->price ?? 0) > ($enrollment->total_paid ?? 0);

                $statusLabels = [
                    'pending' => 'Нужна оплата',
                    'partial' => 'Оплата частично',
                    'paid' => 'Оплачено',
                    'expired' => 'Истёк срок',
                ];
                $statusClasses = [
                    'pending' => 'badge bg-danger-subtle text-danger-emphasis',
                    'partial' => 'badge bg-warning-subtle text-warning-emphasis',
                    'paid' => 'badge bg-success-subtle text-success-emphasis',
                    'expired' => 'badge bg-secondary-subtle text-secondary-emphasis',
                ];
                $status = $enrollment->status ?? 'pending';

                $markDisabled = ! $shiftActive || $alreadyAttended || $needsPayment || ! $slotActive;
                $paymentDisabled = ! $shiftActive || ! $slotActive;

                $markHelper = null;
                if ($alreadyAttended) {
                    $markHelper = 'Сегодня уже отмечен.';
                } elseif ($needsPayment) {
                    $markHelper = 'Сначала примите оплату.';
                } elseif (! $slotActive) {
                    if ($isToday && $nextStartLabel) {
                        $markHelper = 'Занятие начнётся в ' . $nextStartLabel;
                    } elseif ($isToday) {
                        $markHelper = 'Текущий интервал завершён.';
                    } else {
                        $markHelper = 'Сегодня занятий нет.';
                    }
                } elseif (! $shiftActive) {
                    $markHelper = 'Начните смену, чтобы отметить посещение.';
                }

                $paymentHelper = null;
                if (! $shiftActive) {
                    $paymentHelper = 'Начните смену, чтобы принять оплату.';
                } elseif (! $slotActive) {
                    if ($isToday && $nextStartLabel) {
                        $paymentHelper = 'Оплата доступна во время занятия (с ' . $nextStartLabel . ').';
                    } else {
                        $paymentHelper = 'Оплата доступна только во время занятия.';
                    }
                }

                $enrollments[] = [
                    'child' => $child,
                    'package' => $enrollment->package,
                    'schedule_label' => $scheduleLabel,
                    'status_label' => $statusLabels[$status] ?? $status,
                    'status_class' => $statusClasses[$status] ?? 'badge bg-secondary',
                    'needs_payment' => $needsPayment,
                    'already_attended' => $alreadyAttended,
                    'already_trial_attended' => $alreadyTrialAttended,
                    'mark_disabled' => $markDisabled,
                    'mark_helper' => $markHelper,
                    'payment_disabled' => $paymentDisabled,
                    'payment_helper' => $paymentHelper,
                    'enrollment' => $enrollment,
                ];
            }

            $fullScheduleSummary = $section->schedules
                ->sortBy(fn (SectionSchedule $slot) => $slot->weekday * 10000 + (int) $slot->starts_at->format('Hi'))
                ->groupBy('weekday')
                ->map(function ($slots, $day) use ($fullWeekdayNames) {
                    $label = $fullWeekdayNames[$day] ?? $day;
                    $times = $slots->map(fn (SectionSchedule $slot) => $slot->starts_at->format('H:i') . ' – ' . $slot->ends_at->format('H:i'))->implode(', ');
                    return $label . ': ' . $times;
                })
                ->implode('; ');

            return [
                'section' => $section,
                'direction' => $section->direction?->name ?? 'Без направления',
                'room' => $section->room?->name,
                'today_slots' => $todaySlots->map(fn (SectionSchedule $slot) => ($weekdayNames[$slot->weekday] ?? $slot->weekday) . ' ' . $slot->starts_at->format('H:i') . ' – ' . $slot->ends_at->format('H:i'))->values(),
                'active_slot' => $activeSlot ? $activeSlot->starts_at->format('H:i') . ' – ' . $activeSlot->ends_at->format('H:i') : null,
                'next_slot' => $nextSlot ? $nextSlot->starts_at->format('H:i') . ' – ' . $nextSlot->ends_at->format('H:i') : null,
                'full_schedule' => $fullScheduleSummary,
                'enrollments' => $enrollments,
                'has_matches' => $hasMatches,
            ];
        })->groupBy(fn ($card) => $card['direction'])->sortKeys();

        return view('reception.index', [
            'directionGroups' => $sectionCards,
            'q' => $q,
            'hasSearch' => $hasSearch,
            'shift' => $shift,
            'today' => $today->toDateString(),
            'shiftElapsed' => $shiftElapsed,
            'shiftActive' => $shiftActive,
            'shiftBlockReason' => $shiftBlockReason,
            'shiftSetting' => $shiftSetting,
            'shiftCanStop' => $shiftCanStop,
            'shiftStopLockedUntil' => $shiftStopLockedUntil,
            'canManage' => $canManage,
        ]);
    }

    public function mark(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $enrollment = Enrollment::with(['child', 'section', 'package', 'schedule'])
            ->where('child_id', $data['child_id'])
            ->where('section_id', $data['section_id'])
            ->latest('started_at')
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'Прикрепление ребёнка к секции не найдено.');
        }

        if ($enrollment->expires_at && now()->gt($enrollment->expires_at)) {
            return back()->with('error', 'Срок действия прикрепления истёк.');
        }

        if (!is_null($enrollment->visits_left) && $enrollment->visits_left < 1) {
            return back()->with('error', 'У ребёнка закончились посещения по абонементу.');
        }

        $requiredAmount = $enrollment->price ?? ($enrollment->package?->price ?? 0);
        if ($requiredAmount > 0 && $enrollment->total_paid < $requiredAmount) {
            return back()->with('error', 'Сначала примите оплату за прикрепление ребёнка к секции.');
        }

        $schedule = $enrollment->schedule;
        if (!$schedule) {
            return back()->with('error', 'У прикрепления ребёнка к секции не выбрано расписание.');
        }

        $now = now();
        $isToday = (int) $schedule->weekday === $now->isoWeekday();
        $slotStart = $schedule->starts_at->copy()->setDate($now->year, $now->month, $now->day);
        $slotEnd = $schedule->ends_at->copy()->setDate($now->year, $now->month, $now->day);
        $slotActive = $isToday && $now->between($slotStart, $slotEnd);

        if (!$slotActive) {
            return back()->with('error', 'Посещение можно отметить только во время занятия.');
        }

        $attendedOn = $now->toDateString();
        $section = $enrollment->section;
        $child = $enrollment->child;

        $existingAttendance = Attendance::where('child_id', $data['child_id'])
            ->where('section_id', $data['section_id'])
            ->where('attended_on', $attendedOn)
            ->first();

        if ($existingAttendance && $existingAttendance->status === 'attended') {
            return back()->with('info', 'Ребёнок уже отмечен как посетивший занятие сегодня.');
        }

        $statusBefore = $existingAttendance?->status;
        $attendance = null;

        DB::transaction(function () use ($data, $enrollment, $attendedOn, $section, $child, $existingAttendance, $statusBefore, &$attendance) {
            $attendance = Attendance::where('child_id', $data['child_id'])
                ->where('section_id', $data['section_id'])
                ->where('attended_on', $attendedOn)
                ->lockForUpdate()
                ->first();

            if ($attendance) {
                $statusBefore = $attendance->status;
                $attendance->fill([
                    'enrollment_id' => $enrollment->id,
                    'room_id' => $section->room_id,
                    'attended_at' => now(),
                    'status' => 'attended',
                    'source' => 'manual',
                    'marked_by' => auth()->user()->id,
                    'restored_at' => null,
                    'restored_by' => null,
                    'restored_reason' => null,
                ]);
                $attendance->save();

                if ($statusBefore === 'restored' && !is_null($enrollment->visits_left)) {
                    $enrollment->decrement('visits_left');
                }

                $attendance = $attendance;
            } else {
                $attendance = Attendance::create([
                    'child_id' => $data['child_id'],
                    'section_id' => $data['section_id'],
                    'enrollment_id' => $enrollment->id,
                    'room_id' => $section->room_id,
                    'attended_on' => $attendedOn,
                    'attended_at' => now(),
                    'status' => 'attended',
                    'source' => 'manual',
                    'marked_by' => auth()->user()->id,
                ]);

                if (!is_null($enrollment->visits_left)) {
                    $enrollment->decrement('visits_left');
                }
            }

            $enrollment->refresh();

            ActivityLogger::log(auth()->user(), 'child.attendance_marked', $child, [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'schedule_id' => $enrollment->section_schedule_id,
                'attendance_id' => $attendance->id,
                'attended_on' => $attendedOn,
                'status_before' => $statusBefore,
            ]);
        });

        return back()->with('success', 'Посещение отмечено. Ребёнок успешно посетил занятие!');
    }

    public function renew(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $enrollment = Enrollment::with(['child', 'section', 'package'])
            ->where('child_id', $data['child_id'])
            ->where('section_id', $data['section_id'])
            ->latest('started_at')
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'Прикрепление ребёнка к секции не найдено.');
        }

        if ($enrollment->expires_at && now()->gt($enrollment->expires_at)) {
            return back()->with('error', 'Срок действия прикрепления истёк.');
        }

        $requiredAmount = $enrollment->price ?? ($enrollment->package?->price ?? 0);
        if ($requiredAmount > 0 && $enrollment->total_paid < $requiredAmount) {
            return back()->with('error', 'Сначала примите оплату за прикрепление ребёнка к секции.');
        }

        $package = $enrollment->package;
        if (!$package) {
            return back()->with('error', 'У прикрепления не выбран пакет.');
        }

        $now = now();
        $newExpiresAt = null;

        if ($package->billing_type === 'period' && $package->days) {
            $newExpiresAt = $now->copy()->addDays($package->days);
        } elseif ($package->billing_type === 'visits' && $package->visits_count) {
            $enrollment->visits_left = $package->visits_count;
        }

        $enrollment->update([
            'started_at' => $now,
            'expires_at' => $newExpiresAt,
            'status' => 'paid',
        ]);

        ActivityLogger::log(auth()->user(), 'child.enrollment_renewed', $enrollment->child, [
            'section_id' => $enrollment->section_id,
            'section_name' => $enrollment->section->name,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'new_expires_at' => $newExpiresAt?->toDateString(),
            'visits_left' => $enrollment->visits_left,
        ]);

        return back()->with('success', 'Прикрепление продлено. Ребёнок может посещать занятия!');
    }

    public function markTrial(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_comment' => ['nullable', 'string'],
        ]);

        $child = \App\Models\Child::findOrFail($data['child_id']);
        $section = Section::findOrFail($data['section_id']);

        // Проверяем, есть ли пробные занятия в секции
        if (!$section->has_trial) {
            return back()->with('error', 'В данной секции нет пробных занятий.');
        }

        // Проверяем, не было ли уже пробного занятия у этого ребенка в этой секции
        if ($section->hasChildTrialAttendance($child)) {
            return back()->with('error', 'У этого ребенка уже было пробное занятие в данной секции.');
        }

        $now = now();
        $trialAttendance = null;

        DB::transaction(function () use ($request, $data, $child, $section, $now, &$trialAttendance) {
            $trialAttendance = TrialAttendance::create([
                'child_id' => $child->id,
                'section_id' => $section->id,
                'attended_on' => $now->toDateString(),
                'attended_at' => $now,
                'is_free' => $section->trial_is_free,
                'price' => $section->trial_price,
                'paid_amount' => $data['payment_amount'] ?? 0,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_comment' => $data['payment_comment'] ?? null,
                'marked_by' => $request->user()->id,
            ]);

            ActivityLogger::log($request->user(), 'child.trial_attendance_marked', $child, [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'trial_attendance_id' => $trialAttendance->id,
                'is_free' => $trialAttendance->is_free,
                'price' => $trialAttendance->price,
                'paid_amount' => $trialAttendance->paid_amount,
                'attended_on' => $trialAttendance->attended_on->toDateString(),
            ]);
        });

        $message = $trialAttendance->is_free 
            ? 'Пробное занятие отмечено как бесплатное.'
            : 'Пробное занятие отмечено. Сумма: ' . number_format($trialAttendance->price, 2, ',', ' ') . ' ₸';

        return back()->with('success', $message);
    }
}