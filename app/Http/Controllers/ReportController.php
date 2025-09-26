<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $defaultFrom = now()->startOfMonth();
        $defaultTo = now()->endOfDay();

        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : $defaultFrom->copy();
        $to = $toInput ? Carbon::parse($toInput)->endOfDay() : $defaultTo->copy();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $daysInRange = max(1, $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);

        $attendanceTotal = Attendance::whereBetween('attended_on', [$from->toDateString(), $to->toDateString()])->count();
        $uniqueChildren = Attendance::whereBetween('attended_on', [$from->toDateString(), $to->toDateString()])->distinct('child_id')->count('child_id');
        $paymentsTotal = Payment::whereBetween('paid_at', [$from, $to])->sum('amount');
        $newEnrollments = Enrollment::whereBetween('started_at', [$from->toDateString(), $to->toDateString()])->count();

        $attendanceBySection = Attendance::select('section_id', DB::raw('count(*) as total'))
            ->whereBetween('attended_on', [$from->toDateString(), $to->toDateString()])
            ->groupBy('section_id')
            ->with('section')
            ->orderByDesc('total')
            ->get();

        $paymentsBySection = Payment::select('sections.id', 'sections.name', DB::raw('sum(payments.amount) as total'))
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->groupBy('sections.id', 'sections.name')
            ->orderByDesc('total')
            ->get();

        $attendanceTimeline = Attendance::select('attended_on', DB::raw('count(*) as total'))
            ->whereBetween('attended_on', [$from->toDateString(), $to->toDateString()])
            ->groupBy('attended_on')
            ->orderBy('attended_on')
            ->get();

        $shiftRecords = Shift::with('user')
            ->whereBetween('started_at', [$from, $to])
            ->orderBy('started_at')
            ->get()
            ->map(function (Shift $shift) {
                $end = $shift->ended_at ?? $shift->scheduled_end_at ?? now();
                $minutes = $shift->duration_min ?? $shift->started_at->diffInMinutes($end);
                $shift->calculated_duration = $minutes;
                $shift->calculated_duration_human = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
                return $shift;
            });

        $shiftTotals = $shiftRecords
            ->groupBy('user_id')
            ->map(function ($items) {
                /** @var \Illuminate\Support\Collection $items */
                $minutes = $items->sum(fn (Shift $shift) => $shift->calculated_duration);
                $user = $items->first()->user;
                return [
                    'user' => $user,
                    'total_minutes' => $minutes,
                    'shifts_count' => $items->count(),
                ];
            })
            ->values();

        return view('reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'attendanceTotal' => $attendanceTotal,
            'uniqueChildren' => $uniqueChildren,
            'paymentsTotal' => $paymentsTotal,
            'newEnrollments' => $newEnrollments,
            'attendanceBySection' => $attendanceBySection,
            'paymentsBySection' => $paymentsBySection,
            'attendanceTimeline' => $attendanceTimeline,
            'shiftRecords' => $shiftRecords,
            'shiftTotals' => $shiftTotals,
            'daysInRange' => $daysInRange,
        ]);
    }
}
