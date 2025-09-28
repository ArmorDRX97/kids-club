<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Package;
use App\Models\SectionSchedule;
use App\Models\TrialAttendance;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'section_schedule_id' => ['required', 'exists:section_schedules,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'started_at' => ['required', 'date'],
            'payment_comment' => ['nullable', 'string'],
            'is_trial' => ['nullable', 'boolean'],
        ]);

        $schedule = SectionSchedule::where('id', $data['section_schedule_id'])
            ->where('section_id', $data['section_id'])
            ->firstOrFail();

        $section = $schedule->section;
        $isTrial = (bool) ($data['is_trial'] ?? false);
        
        // Если это пробное занятие, проверяем возможность
        if ($isTrial) {
            if (!$section->has_trial) {
                return back()->with('error', 'В данной секции нет пробных занятий.');
            }
            
            $child = \App\Models\Child::findOrFail($data['child_id']);
            if ($section->hasChildTrialAttendance($child)) {
                return back()->with('error', 'У этого ребенка уже было пробное занятие в данной секции.');
            }
        } else {
            // Для обычной записи пакет обязателен
            if (!$data['package_id']) {
                return back()->with('error', 'Выберите пакет для записи.');
            }
        }

        $package = null;
        if ($data['package_id']) {
            $package = Package::where('id', $data['package_id'])
                ->where('section_id', $data['section_id'])
                ->firstOrFail();
        }

        DB::transaction(function () use ($request, $data, $package, $schedule, $section, $isTrial) {
            if ($isTrial) {
                // Для пробного занятия создаем только TrialAttendance
                TrialAttendance::create([
                    'child_id' => $data['child_id'],
                    'section_id' => $data['section_id'],
                    'attended_on' => $data['started_at'],
                    'attended_at' => now(),
                    'is_free' => $section->trial_is_free,
                    'price' => $section->trial_price,
                    'paid_amount' => 0,
                    'payment_method' => null,
                    'payment_comment' => $data['payment_comment'] ?? null,
                    'marked_by' => $request->user()->id,
                ]);
            } else {
                // Для обычной записи создаем Enrollment
                $enrollment = new Enrollment([
                    'child_id' => $data['child_id'],
                    'section_id' => $data['section_id'],
                    'section_schedule_id' => $schedule->id,
                    'package_id' => $package->id,
                    'started_at' => $data['started_at'],
                    'price' => $package->price,
                    'total_paid' => 0,
                    'status' => 'pending',
                ]);

                if ($package->billing_type === 'visits') {
                    $enrollment->visits_left = $package->visits_count;
                } elseif ($package->billing_type === 'period' && $package->days) {
                    $enrollment->expires_at = Carbon::parse($data['started_at'])->addDays($package->days);
                }

                $enrollment->save();
                $enrollment->load(['child', 'section', 'package', 'schedule']);
                $enrollment->refreshStatus();
            }

            // Логирование
            $child = \App\Models\Child::findOrFail($data['child_id']);
            if ($isTrial) {
                ActivityLogger::log($request->user(), 'child.trial_attendance_marked', $child, [
                    'section_id' => $section->id,
                    'section_name' => $section->name,
                    'is_free' => $section->trial_is_free,
                    'price' => $section->trial_price,
                    'attended_on' => $data['started_at'],
                ]);
            } else {
                ActivityLogger::log($request->user(), 'child.enrollment_added', $child, [
                    'section_id' => $section->id,
                    'section_name' => $section->name,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'schedule_id' => $schedule->id,
                    'schedule_label' => $this->formatScheduleLabel($schedule),
                    'started_at' => $data['started_at'],
                ]);
            }
        });

        $message = $isTrial ? 'Пробное занятие успешно записано.' : 'Ребёнок успешно записан на секцию.';
        return back()->with('success', $message);
    }

    protected function formatScheduleLabel(SectionSchedule $schedule): string
    {
        $weekdayMap = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье',
        ];

        $day = $weekdayMap[$schedule->weekday] ?? $schedule->weekday;

        return sprintf('%s %s – %s', $day, $schedule->starts_at->format('H:i'), $schedule->ends_at->format('H:i'));
    }
}
