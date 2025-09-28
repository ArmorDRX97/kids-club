<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Section;
use App\Models\TrialAttendance;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialAttendanceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_comment' => ['nullable', 'string'],
        ]);

        $child = Child::findOrFail($data['child_id']);
        $section = Section::findOrFail($data['section_id']);

        // Проверяем, есть ли пробные занятия в секции
        if (!$section->has_trial) {
            return back()->with('error', 'В данной секции нет пробных занятий.');
        }

        // Проверяем, не было ли уже пробного занятия у этого ребенка в этой секции
        if ($section->hasChildTrialAttendance($child)) {
            return back()->with('error', 'У этого ребенка уже было пробное занятие в данной секции.');
        }

        $now = now();
        $trialAttendance = null;

        DB::transaction(function () use ($request, $data, $child, $section, $now, &$trialAttendance) {
            $trialAttendance = TrialAttendance::create([
                'child_id' => $child->id,
                'section_id' => $section->id,
                'attended_on' => $now->toDateString(),
                'attended_at' => $now,
                'is_free' => $section->trial_is_free,
                'price' => $section->trial_price,
                'paid_amount' => $data['payment_amount'] ?? 0,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_comment' => $data['payment_comment'] ?? null,
                'marked_by' => $request->user()->id,
            ]);

            ActivityLogger::log($request->user(), 'child.trial_attendance_marked', $child, [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'trial_attendance_id' => $trialAttendance->id,
                'is_free' => $trialAttendance->is_free,
                'price' => $trialAttendance->price,
                'paid_amount' => $trialAttendance->paid_amount,
                'attended_on' => $trialAttendance->attended_on->toDateString(),
            ]);
        });

        $message = $trialAttendance->is_free 
            ? 'Пробное занятие отмечено как бесплатное.'
            : 'Пробное занятие отмечено. Сумма: ' . number_format($trialAttendance->price, 2, ',', ' ') . ' ₸';

        return back()->with('success', $message);
    }

    public function show(Child $child)
    {
        $child->load([
            'trialAttendances' => function ($query) {
                $query->with(['section', 'marker'])->latest('attended_on');
            }
        ]);

        return view('children.trial-attendances', [
            'child' => $child,
            'trialAttendances' => $child->trialAttendances,
        ]);
    }
}
