<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Package;
use App\Models\SectionSchedule;
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
            'package_id' => ['required', 'exists:packages,id'],
            'started_at' => ['required', 'date'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_comment' => ['nullable', 'string'],
        ]);

        $package = Package::where('id', $data['package_id'])
            ->where('section_id', $data['section_id'])
            ->firstOrFail();

        $schedule = SectionSchedule::where('id', $data['section_schedule_id'])
            ->where('section_id', $data['section_id'])
            ->firstOrFail();

        DB::transaction(function () use ($request, $data, $package, $schedule) {
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

            $paymentAmount = $data['payment_amount'] ?? null;
            if ($paymentAmount && $paymentAmount > 0) {
                $payment = $enrollment->payments()->create([
                    'child_id' => $enrollment->child_id,
                    'amount' => $paymentAmount,
                    'paid_at' => now(),
                    'method' => $data['payment_method'] ?? null,
                    'comment' => $data['payment_comment'] ?? null,
                    'user_id' => $request->user()->id,
                ]);

                $enrollment->total_paid = ($enrollment->total_paid ?? 0) + $payment->amount;
            }

            $enrollment->refreshStatus();

            ActivityLogger::log($request->user(), 'child.enrollment_added', $enrollment->child, [
                'section_id' => $enrollment->section_id,
                'section_name' => $enrollment->section?->name,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'schedule_id' => $schedule->id,
                'schedule_label' => $this->formatScheduleLabel($schedule),
                'enrollment_id' => $enrollment->id,
                'started_at' => $enrollment->started_at?->toDateString(),
            ]);
        });

        return back()->with('success', 'Ребёнок успешно записан на секцию.');
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
