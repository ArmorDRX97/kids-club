<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Attendance, Enrollment};


class AttendanceController extends Controller {
    public function store(Request $request){
        $data = $request->validate([
            'child_id' => ['required','exists:children,id'],
            'section_id' => ['required','exists:sections,id'],
            'room_id' => ['nullable','exists:rooms,id']
        ]);
// Ищем активную подписку на эту секцию
        $enrollment = Enrollment::where('child_id',$data['child_id'])
            ->where('section_id',$data['section_id'])
            ->latest('started_at')->first();


        if (!$enrollment) return back()->with('error','Нет активного пакета для этой секции.');
// Проверяем срок/остатки
        if ($enrollment->expires_at && now()->gt($enrollment->expires_at))
            return back()->with('error','Срок пакета истёк. Нужно продлить.');

        if (!is_null($enrollment->visits_left) && $enrollment->visits_left < 1)
            return back()->with('error','Посещения закончились. Нужно продлить.');

        $requiredAmount = $enrollment->price ?? ($enrollment->package?->price ?? 0);
        if ($requiredAmount > 0 && $enrollment->total_paid < $requiredAmount)
            return back()->with('error','Отметка невозможна — оплата ещё не поступила.');

        $today = now()->toDateString();
        $exists = Attendance::where('child_id', $data['child_id'])
            ->where('section_id', $data['section_id'])
            ->where('attended_on', $today)
            ->exists();
        if ($exists)
            return back()->with('info','Сегодня уже отмечен.');

        $att = Attendance::create([
            'child_id' => $data['child_id'],
            'section_id' => $data['section_id'],
            'enrollment_id' => $enrollment->id,
            'room_id' => $data['room_id'] ?? null,
            'attended_on' => $today,
            'attended_at' => now(),
            'marked_by' => $request->user()->id,
        ]);


// Списываем посещение если пакет по занятиям
        if (!is_null($enrollment->visits_left)){
            $enrollment->decrement('visits_left');
        }
        return back()->with('success','Посещение отмечено. Осталось: '.($enrollment->visits_left ?? 'безлимит'));
    }
}
