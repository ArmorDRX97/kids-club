<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceAutoMarker
{
    public function markMissedUpTo(Carbon $reference, ?int $lookbackDays = null): int
    {
        $lookback = $lookbackDays ?? (int) config('kidsclub.attendance.auto_mark_lookback_days', 1);
        $processed = 0;

        for ($offset = $lookback; $offset >= 0; $offset--) {
            $targetDate = $reference->copy()->subDays($offset)->startOfDay();
            $processed += $this->markForDate($targetDate, $reference);
        }

        return $processed;
    }

    protected function markForDate(Carbon $date, Carbon $reference): int
    {
        if ($date->greaterThan($reference->copy()->endOfDay())) {
            return 0;
        }

        $weekday = $date->isoWeekday();
        $graceMinutes = (int) config('kidsclub.attendance.auto_mark_grace_minutes', 15);
        $processed = 0;

        Enrollment::query()
            ->with(['child', 'section', 'package', 'schedule'])
            ->whereNotNull('section_schedule_id')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'expired');
            })
            ->whereDate('started_at', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $date->toDateString());
            })
            ->whereHas('child', fn ($query) => $query->where('is_active', true))
            ->whereHas('schedule', fn ($query) => $query->where('weekday', $weekday))
            ->chunkById(200, function ($enrollments) use (&$processed, $date, $reference, $graceMinutes) {
                foreach ($enrollments as $enrollment) {
                    $child = $enrollment->child;
                    $section = $enrollment->section;
                    $schedule = $enrollment->schedule;

                    if (! $child || ! $section || ! $schedule) {
                        continue;
                    }

                    $slotEnd = $schedule->ends_at->copy()->setDate($date->year, $date->month, $date->day);
                    $cutoff = $slotEnd->copy()->addMinutes($graceMinutes);
                    if ($reference->lt($cutoff)) {
                        continue;
                    }

                    $requiredAmount = $enrollment->price ?? ($enrollment->package?->price ?? 0);
                    if ($requiredAmount > 0 && (float) $enrollment->total_paid < (float) $requiredAmount) {
                        continue;
                    }

                    if (! is_null($enrollment->visits_left) && $enrollment->visits_left < 1) {
                        continue;
                    }

                    $alreadyMarked = Attendance::where('child_id', $child->id)
                        ->where('section_id', $section->id)
                        ->where('attended_on', $date->toDateString())
                        ->exists();

                    if ($alreadyMarked) {
                        continue;
                    }

                    DB::transaction(function () use ($enrollment, $child, $section, $date, &$processed) {
                        $attendance = Attendance::create([
                            'child_id' => $child->id,
                            'section_id' => $section->id,
                            'enrollment_id' => $enrollment->id,
                            'room_id' => $section->room_id,
                            'attended_on' => $date->toDateString(),
                            'attended_at' => null,
                            'status' => 'missed',
                            'source' => 'system',
                        ]);

                        if (! is_null($enrollment->visits_left) && $enrollment->visits_left > 0) {
                            $enrollment->decrement('visits_left');
                        }

                        ActivityLogger::log(null, 'child.absence_marked', $child, [
                            'section_id' => $section->id,
                            'section_name' => $section->name,
                            'schedule_id' => $enrollment->section_schedule_id,
                            'attendance_id' => $attendance->id,
                            'attended_on' => $date->toDateString(),
                        ]);

                        $processed++;
                    });
                }
            });

        return $processed;
    }
}
