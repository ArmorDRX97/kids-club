<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Support\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
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
        $withinSlot = $isToday && $now->between($slotStart, $slotEnd);

        if (!$withinSlot) {
            return back()->with('error', 'Посещение можно отметить только во время занятия.');
        }

        $duplicateMessage = 'Ребёнок уже отмечен как посетивший занятие.';
        $successPrefix = 'Посещение отмечено. Осталось посещений: ';
        $successUnlimitedLabel = 'Безлимитно';
        $remainingVisits = $enrollment->visits_left;

        $today = $now->toDateString();
        $section = $enrollment->section;
        $child = $enrollment->child;

        $existingAttendance = Attendance::where('child_id', $data['child_id'])
            ->where('section_id', $data['section_id'])
            ->where('attended_on', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->status === 'attended') {
            return back()->with('info', $duplicateMessage);
        }

        $attendanceRecord = null;
        $statusBefore = $existingAttendance?->status;

        try {
            DB::transaction(function () use ($request, $data, $today, $enrollment, $section, $child, &$remainingVisits, &$attendanceRecord, &$statusBefore) {
                $attendance = Attendance::where('child_id', $data['child_id'])
                    ->where('section_id', $data['section_id'])
                    ->where('attended_on', $today)
                    ->lockForUpdate()
                    ->first();

                if ($attendance) {
                    $statusBefore = $attendance->status;
                    $attendance->fill([
                        'enrollment_id' => $enrollment->id,
                        'room_id' => $data['room_id'] ?? $section?->room_id,
                        'attended_at' => now(),
                        'status' => 'attended',
                        'source' => 'manual',
                        'marked_by' => $request->user()->id,
                        'restored_at' => null,
                        'restored_by' => null,
                        'restored_reason' => null,
                    ]);
                    $attendance->save();

                    if ($statusBefore === 'restored' && !is_null($enrollment->visits_left)) {
                        $enrollment->decrement('visits_left');
                    }

                    $attendanceRecord = $attendance;
                } else {
                    $attendanceRecord = Attendance::create([
                        'child_id' => $data['child_id'],
                        'section_id' => $data['section_id'],
                        'enrollment_id' => $enrollment->id,
                        'room_id' => $data['room_id'] ?? $section?->room_id,
                        'attended_on' => $today,
                        'attended_at' => now(),
                        'status' => 'attended',
                        'source' => 'manual',
                        'marked_by' => $request->user()->id,
                    ]);

                    if (!is_null($enrollment->visits_left)) {
                        $enrollment->decrement('visits_left');
                    }
                }

                $enrollment->refresh();
                $remainingVisits = $enrollment->visits_left;

                ActivityLogger::log($request->user(), 'child.attendance_marked', $child, [
                    'section_id' => $enrollment->section_id,
                    'section_name' => $section?->name,
                    'schedule_id' => $enrollment->section_schedule_id,
                    'attendance_id' => $attendanceRecord->id,
                    'attended_on' => $today,
                    'status_before' => $statusBefore,
                ]);
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                return back()->with('info', $duplicateMessage);
            }

            throw $exception;
        }

        $successMessage = $successPrefix . ($remainingVisits ?? $successUnlimitedLabel);

        return back()->with('success', $successMessage);
    }
}