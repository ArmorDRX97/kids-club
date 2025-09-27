<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string'],
        ]);

        $enrollment = Enrollment::with(['child', 'section', 'package'])->findOrFail($data['enrollment_id']);

        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'child_id' => $enrollment->child_id,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? now(),
            'method' => $data['method'] ?? null,
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

        return back()->with('success', 'Платёж сохранён.');
    }
}


