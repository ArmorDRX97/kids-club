<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Payment, Enrollment};


class PaymentController extends Controller {
    public function store(Request $request){
        $data = $request->validate([
            'enrollment_id' => ['required','exists:enrollments,id'],
            'amount' => ['required','numeric','min:0.01'],
            'paid_at' => ['nullable','date'],
            'method' => ['nullable','string','max:50'],
            'comment' => ['nullable','string']
        ]);
        $enrollment = Enrollment::findOrFail($data['enrollment_id']);
        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'child_id' => $enrollment->child_id,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? now(),
            'method' => $data['method'] ?? null,
            'comment' => $data['comment'] ?? null,
            'user_id' => $request->user()->id,
        ]);
// обновить сумму оплачено и статус
        $enrollment->total_paid += $payment->amount;
        $enrollment->refreshStatus();
        return back()->with('success','Платёж сохранён.');
    }
}
