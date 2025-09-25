<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Attendance, Enrollment, Child};


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


        $att = Attendance::create([
            'child_id' => $data['child_id'],
            'section_id' => $data['section_id'],
            'enrollment_id' => $enrollment->id,
            'room_id' => $data['room_id'] ?? null,
            'attended_at' => now(),
            'marked_by' => $request->user()->id,
        ]);


// Списываем посещение если пакет по занятиям
        if (!is_null($enrollment->visits_left)){
            if ($enrollment->visits_left < 1)
                return back()->with('error','Посещения закончились. Нужно продлить.');
            $enrollment->visits_left -= 1;
            $enrollment->save();
        }
        return back()->with('success','Посещение отмечено. Осталось: '.($enrollment->visits_left ?? 'безлимит'));
    }
}
