<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionSchedule;
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

        $sectionCards = $sections->map(function (Section $section) use ($weekday, $now, $attendanceToday, $q, $hasSearch, $weekdayNames, $fullWeekdayNames, $shiftActive) {
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

        public function mark(Request \)
    {
        \ = \->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        \ = Enrollment::with(['child', 'section', 'package', 'schedule'])
            ->where('child_id', \['child_id'])
            ->where('section_id', \['section_id'])
            ->latest('started_at')
            ->first();

        if (! \) {
            return back()->with('error', '�?��' ����'��?�?�?�?�? ���?���?����>��?��? �� �?�'�?�� �?���Ő��.');
        }

        if (\->expires_at && now()->gt(\->expires_at)) {
            return back()->with('error', '���?�?�� �?����?�'�?��? �������'�� ��?�'�'��.');
        }

        if (! is_null(\->visits_left) && \->visits_left < 1) {
            return back()->with('error', '�"�?�?�'�?���?�<�� ���?�?��%��?��? ������?�?�ؐ�>��?�?.');
        }

        \ = \->price ?? (\->package?->price ?? 0);
        if (\ > 0 && \->total_paid < \) {
            return back()->with('error', '�?��>�?���? �?�'�?��'��'�? ���?�?��%��?��� �+��� �?���>���'�< �������'��.');
        }

        \ = \->schedule;
        if (! \) {
            return back()->with('error', '�"�>�? �?�'�?�?�? ���?���?����>��?��? �?�� �����?���? �?�?��?��?�?�?�� ��?�'��?�?���>.');
        }

        \ = now();
        \ = (int) \->weekday === \->isoWeekday();
        \ = \->starts_at->copy()->setDate(\->year, \->month, \->day);
        \ = \->ends_at->copy()->setDate(\->year, \->month, \->day);
        \ = \ && \->between(\, \);

        if (! \) {
            return back()->with('error', '�?�'�?��'�?�'�� �?��+�'�?��� �?�? �?�?��?�? �����?�?�'��?.');
        }

        \ = \->toDateString();
        \ = \->section;
        \ = \->child;

        \ = Attendance::where('child_id', \['child_id'])
            ->where('section_id', \['section_id'])
            ->where('attended_on', \)
            ->first();

        if (\ && \->status === 'attended') {
            return back()->with('info', '����+�'�?�?�� �?�\u0014�� �?�'�?��ؐ�? �?��?�?�?�?�?.');
        }

        \ = \->status;
        \ = null;

        DB::transaction(function () use (\, \, \, \, \, \, &\, &\) {
            \ = Attendance::where('child_id', \['child_id'])
                ->where('section_id', \['section_id'])
                ->where('attended_on', \)
                ->lockForUpdate()
                ->first();

            if (\) {
                \ = \->status;
                \->fill([
                    'enrollment_id' => \->id,
                    'room_id' => \->room_id,
                    'attended_at' => now(),
                    'status' => 'attended',
                    'source' => 'manual',
                    'marked_by' => \->user()->id,
                    'restored_at' => null,
                    'restored_by' => null,
                    'restored_reason' => null,
                ]);
                \->save();

                if (\ === 'restored' && ! is_null(\->visits_left)) {
                    \->decrement('visits_left');
                }

                \ = \;
            } else {
                \ = Attendance::create([
                    'child_id' => \['child_id'],
                    'section_id' => \['section_id'],
                    'enrollment_id' => \->id,
                    'room_id' => \->room_id,
                    'attended_on' => \,
                    'attended_at' => now(),
                    'status' => 'attended',
                    'source' => 'manual',
                    'marked_by' => \->user()->id,
                ]);

                if (! is_null(\->visits_left)) {
                    \->decrement('visits_left');
                }
            }

            \->refresh();

            ActivityLogger::log(\->user(), 'child.attendance_marked', \, [
                'section_id' => \->section_id,
                'section_name' => \->name,
                'schedule_id' => \->section_schedule_id,
                'attendance_id' => \->id,
                'attended_on' => \,
                'status_before' => \,
            ]);
        });

        return back()->with('success', '�?�?�?��%��?��� �?�'�?��ؐ�?�?. �?�?��?�'�?�?�?�? �����?�?�'��?!');
    }\n}


