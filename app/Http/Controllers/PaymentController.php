<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TrialAttendance;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'trial_id' => ['nullable', 'exists:trial_attendances,id'],
            'payment_type' => ['required', 'string', 'in:enrollment,trial'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:cash,card'],
            'comment' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            if ($data['payment_type'] === 'enrollment') {
                // Обычная оплата за прикрепление
                $enrollment = Enrollment::with(['child', 'section', 'package', 'schedule'])->findOrFail($data['enrollment_id']);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'child_id' => $enrollment->child_id,
                    'amount' => $data['amount'],
                    'paid_at' => $data['paid_at'] ?? now(),
                    'method' => $data['payment_method'] ?? null,
                    'comment' => $data['comment'] ?? null,
                    'user_id' => $request->user()->id,
                ]);

                $enrollment->total_paid = ($enrollment->total_paid ?? 0) + $payment->amount;
                $enrollment->save();
                $enrollment->refreshStatus();

                ActivityLogger::log($request->user(), 'child.payment_recorded', $enrollment->child, [
                    'section_id' => $enrollment->section_id,
                    'section_name' => $enrollment->section?->name,
                    'package_id' => $enrollment->package_id,
                    'package_name' => $enrollment->package?->name,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'enrollment_id' => $enrollment->id,
                ]);
            } else {
                // Оплата за пробное занятие
                $trialAttendance = TrialAttendance::with(['child', 'section'])->findOrFail($data['trial_id']);
                
                // Проверяем, что это день пробного занятия
                $attendedOn = $trialAttendance->attended_on;
                $today = now()->toDateString();
                if ($attendedOn->toDateString() !== $today) {
                    return back()->with('error', 'Оплата за пробное занятие доступна только в день пробного занятия.');
                }

                // Обновляем пробное занятие
                $trialAttendance->paid_amount = $data['amount'];
                $trialAttendance->payment_method = $data['payment_method'] ?? null;
                $trialAttendance->payment_comment = $data['comment'] ?? null;
                $trialAttendance->save();

                ActivityLogger::log($request->user(), 'child.trial_payment_recorded', $trialAttendance->child, [
                    'section_id' => $trialAttendance->section_id,
                    'section_name' => $trialAttendance->section->name,
                    'trial_attendance_id' => $trialAttendance->id,
                    'amount' => $trialAttendance->paid_amount,
                    'method' => $trialAttendance->payment_method,
                    'attended_on' => $trialAttendance->attended_on->toDateString(),
                ]);
            }
        });

        return back()->with('success', 'Оплата сохранена.');
    }
}
