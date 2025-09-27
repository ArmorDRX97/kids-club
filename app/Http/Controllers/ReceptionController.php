<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use App\Services\ShiftManager;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $todayDow = now()->isoWeekday();
        $todayDom = now()->day;

        $sections = Section::where('is_active', true)
            ->where(function ($s) use ($todayDow, $todayDom) {
                $s->where(function ($w) use ($todayDow) {
                    $w->where('schedule_type', 'weekly')->whereJsonContains('weekdays', $todayDow);
                })->orWhere(function ($w) use ($todayDom) {
                    $w->where('schedule_type', 'monthly')->whereJsonContains('month_days', $todayDom);
                });
            })
            ->with(['room', 'parent'])
            ->orderBy('name')
            ->get();

        $perPage = 10;
        $today = now()->toDateString();
        $user = $request->user();

        $sectionCards = $sections->map(function (Section $section) use ($request, $q, $perPage, $today) {
            $query = Enrollment::with(['child', 'package'])
                ->where('section_id', $section->id)
                ->whereHas('child', fn ($childQuery) => $childQuery->where('is_active', true));

            if ($q !== '') {
                $query->whereHas('child', function ($childQuery) use ($q) {
                    $childQuery->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%")
                        ->orWhere('patronymic', 'like', "%$q%")
                        ->orWhere('parent_phone', 'like', "%$q%")
                        ->orWhere('parent2_phone', 'like', "%$q%")
                        ->orWhere('child_phone', 'like', "%$q%");
                });
            }

            $pageParam = 'p_' . $section->id;
            $page = max(1, $request->integer($pageParam, 1));

            $total = (clone $query)->count();
            $enrollments = (clone $query)
                ->orderByDesc('started_at')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $attendedToday = Attendance::where('section_id', $section->id)
                ->where('attended_on', $today)
                ->pluck('child_id')
                ->all();

            return [
                'section' => $section,
                'enrollments' => $enrollments,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'attended_today' => $attendedToday,
            ];
        });

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

        if ($canManage) {
            $shiftBlockReason = $shiftActive ? null : 'Начните смену, чтобы отмечать посещения и принимать оплату.';
        } else {
            $shiftBlockReason = $shiftActive ? null : 'Рабочие действия доступны только ресепшионистам.';
        }

        return view('reception.index', [
            'sectionCards' => $sectionCards,
            'q' => $q,
            'shift' => $shift,
            'today' => $today,
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

        $child = Child::where('id', $data['child_id'])->where('is_active', true)->firstOrFail();
        $enrollment = Enrollment::where('child_id', $child->id)
            ->where('section_id', $data['section_id'])
            ->latest('started_at')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Нет активного пакета для этой секции.');
        }

        if ($enrollment->expires_at && now()->gt($enrollment->expires_at)) {
            return back()->with('error', 'Срок пакета истёк.');
        }

        if (! is_null($enrollment->visits_left) && $enrollment->visits_left < 1) {
            return back()->with('error', 'Посещения закончились.');
        }

        $requiredAmount = $enrollment->price ?? ($enrollment->package?->price ?? 0);
        if ($requiredAmount > 0 && $enrollment->total_paid < $requiredAmount) {
            return back()->with('error', 'Отметка невозможна — оплата ещё не поступила.');
        }

        $today = now()->toDateString();
        $exists = Attendance::where('child_id', $child->id)
            ->where('section_id', $data['section_id'])
            ->where('attended_on', $today)
            ->exists();

        if ($exists) {
            return back()->with('info', 'Сегодня уже отмечен.');
        }

        $attendance = Attendance::create([
            'child_id' => $child->id,
            'section_id' => $data['section_id'],
            'enrollment_id' => $enrollment->id,
            'room_id' => $enrollment->section->room_id,
            'attended_on' => $today,
            'attended_at' => now(),
            'marked_by' => $request->user()->id,
        ]);

        if (! is_null($enrollment->visits_left)) {
            $enrollment->decrement('visits_left');
        }

        ActivityLogger::log($request->user(), 'child.attendance_marked', $child, [
            'section_id' => $enrollment->section_id,
            'section_name' => $enrollment->section?->name,
            'enrollment_id' => $enrollment->id,
            'attendance_id' => $attendance->id,
            'attended_on' => $today,
        ]);

        return back()->with('success', 'Отмечено.');
    }

    public function renew(Request $request)
    {
        // В данный момент продление обрабатывается через модальные окна в карточке ребёнка
        return back()->with('info', 'Откройте карточку ребёнка и воспользуйтесь кнопкой «Оплатить/продлить».');
    }
}
