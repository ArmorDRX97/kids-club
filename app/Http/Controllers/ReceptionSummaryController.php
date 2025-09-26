<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReceptionSummaryController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period');
        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        if (!$period && !$fromInput && !$toInput) {
            $period = 'today';
        }

        $now = Carbon::now();
        $defaultFrom = $now->copy()->startOfDay();
        $defaultTo = $now->copy()->endOfDay();

        if ($period === 'yesterday') {
            $defaultFrom = $now->copy()->subDay()->startOfDay();
            $defaultTo = $now->copy()->subDay()->endOfDay();
        } elseif ($period === 'week') {
            $defaultFrom = $now->copy()->subDays(6)->startOfDay();
            $defaultTo = $now->copy()->endOfDay();
        }

        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : $defaultFrom->copy();
        $to = $toInput ? Carbon::parse($toInput)->endOfDay() : $defaultTo->copy();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $attendanceQuery = Attendance::with(['child', 'section', 'marker'])
            ->whereBetween('attended_on', [$from->toDateString(), $to->toDateString()])
            ->whereHas('marker', function ($query) {
                $query->role(User::ROLE_RECEPTIONIST);
            })
            ->orderByDesc('attended_at');

        $attendances = $attendanceQuery->get();

        $totalVisits = $attendances->count();
        $uniqueChildren = $attendances->pluck('child_id')->unique()->count();

        $sectionsSummary = $attendances
            ->groupBy('section_id')
            ->map(function ($items) {
                $section = $items->first()->section;
                return [
                    'section' => $section,
                    'visits' => $items->count(),
                    'unique_children' => $items->pluck('child_id')->unique()->count(),
                ];
            })
            ->sortByDesc('visits')
            ->values();

        $payments = Payment::with(['child', 'enrollment.section', 'user'])
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas('user', function ($query) {
                $query->role(User::ROLE_RECEPTIONIST);
            })
            ->orderByDesc('paid_at')
            ->get();

        $paymentsTotal = $payments->sum('amount');

        $rangeStart = $from->copy();
        $rangeEnd = $to->copy();

        $shiftRecords = Shift::with('user')
            ->whereHas('user', function ($query) {
                $query->role(User::ROLE_RECEPTIONIST);
            })
            ->where('started_at', '<=', $rangeEnd)
            ->where(function ($query) use ($rangeStart) {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', $rangeStart);
            })
            ->orderBy('started_at')
            ->get()
            ->map(function (Shift $shift) use ($rangeStart, $rangeEnd) {
                $shiftStart = $shift->started_at->copy();
                $shiftEnd = $shift->ended_at
                    ?? ($shift->scheduled_end_at && $shift->scheduled_end_at->lessThan(now())
                        ? $shift->scheduled_end_at->copy()
                        : now());

                if ($shiftEnd->greaterThan($rangeEnd)) {
                    $shiftEnd = $rangeEnd->copy();
                }
                if ($shiftStart->lessThan($rangeStart)) {
                    $shiftStart = $rangeStart->copy();
                }

                $minutes = $shiftEnd->greaterThan($shiftStart)
                    ? $shiftStart->diffInMinutes($shiftEnd)
                    : 0;

                $shift->summary_minutes = $minutes;
                $shift->summary_duration_human = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);

                return $shift;
            });

        $shiftTotals = $shiftRecords
            ->groupBy('user_id')
            ->map(function ($items) {
                $minutes = $items->sum(fn (Shift $shift) => $shift->summary_minutes);
                $user = $items->first()->user;

                return [
                    'user' => $user,
                    'total_minutes' => $minutes,
                    'total_formatted' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
                    'shifts_count' => $items->count(),
                ];
            })
            ->sortByDesc('total_minutes')
            ->values();

        $quickRanges = [
            'today' => 'Сегодня',
            'yesterday' => 'Вчера',
            'week' => 'Последние 7 дней',
        ];

        return view('reception.summary', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'period' => $period,
            'quickRanges' => $quickRanges,
            'totalVisits' => $totalVisits,
            'uniqueChildren' => $uniqueChildren,
            'sectionsSummary' => $sectionsSummary,
            'attendances' => $attendances,
            'payments' => $payments,
            'paymentsTotal' => $paymentsTotal,
            'shiftTotals' => $shiftTotals,
            'shiftRecords' => $shiftRecords,
        ]);
    }
}
