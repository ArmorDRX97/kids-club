<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Direction;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Section;
use App\Models\SectionSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $prevMonthStart = $startOfMonth->copy()->subMonth();
        $prevMonthEnd = $startOfMonth->copy()->subSecond();
        $startOfWeek = $now->copy()->startOfWeek();
        $prevWeekStart = $startOfWeek->copy()->subWeek();
        $prevWeekEnd = $prevWeekStart->copy()->endOfWeek();

        $totalChildren = Child::count();
        $activeChildren = Child::active()->count();
        $newChildrenThisMonth = Child::whereBetween('created_at', [$startOfMonth, $now])->count();
        $newChildrenLastMonth = Child::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();

        $sectionsCount = Section::count();
        $activeSections = Section::where('is_active', true)->count();
        $directionsCount = Direction::count();

        $activeEnrollments = Enrollment::where('status', '!=', 'expired')->count();
        $outstandingBalance = Enrollment::whereIn('status', ['pending', 'partial'])
            ->get(['price', 'total_paid'])
            ->sum(function (Enrollment $enrollment) {
                $remaining = (float) $enrollment->price - (float) $enrollment->total_paid;
                return $remaining > 0 ? $remaining : 0;
            });

        $paymentsThisMonth = (float) Payment::whereBetween('paid_at', [$startOfMonth, $now])->sum('amount');
        $paymentsLastMonth = (float) Payment::whereBetween('paid_at', [$prevMonthStart, $prevMonthEnd])->sum('amount');
        $avgPaymentThisMonth = (float) Payment::whereBetween('paid_at', [$startOfMonth, $now])->avg('amount');

        $attendanceThisWeek = Attendance::whereBetween('attended_on', [$startOfWeek->toDateString(), $now->copy()->endOfWeek()->toDateString()])->count();
        $attendanceLastWeek = Attendance::whereBetween('attended_on', [$prevWeekStart->toDateString(), $prevWeekEnd->toDateString()])->count();

        $recentPayments = Payment::with(['child', 'enrollment.section'])
            ->orderByDesc('paid_at')
            ->limit(6)
            ->get();

        $expiringEnrollments = Enrollment::with(['child', 'section'])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now->copy()->startOfDay(), $now->copy()->addDays(14)->endOfDay()])
            ->orderBy('expires_at')
            ->limit(10)
            ->get()
            ->each(function (Enrollment $enrollment) use ($now) {
                $enrollment->days_left = $now->copy()->startOfDay()->diffInDays($enrollment->expires_at, false);
            });

        $monthLabels = [1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'май', 6 => 'июн', 7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'];

        $monthsRange = collect(range(0, 11))->map(fn (int $index) => $now->copy()->subMonths(11 - $index)->startOfMonth());
        $childGrowthRaw = Child::where('created_at', '>=', $monthsRange->first())
            ->get(['id', 'created_at'])
            ->groupBy(fn (Child $child) => $child->created_at->format('Y-m'))
            ->map->count();
        $childrenGrowthLabels = $monthsRange->map(fn (Carbon $month) => ($monthLabels[$month->month] ?? $month->format('M')) . ' ' . $month->format('Y'));
        $childrenGrowthValues = $monthsRange->map(fn (Carbon $month) => (int) ($childGrowthRaw[$month->format('Y-m')] ?? 0));

        $paymentsRaw = Payment::where('paid_at', '>=', $monthsRange->first())
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn (Collection $group) => $group->sum(fn (Payment $payment) => (float) $payment->amount));
        $paymentsTrendValues = $monthsRange->map(fn (Carbon $month) => round($paymentsRaw[$month->format('Y-m')] ?? 0, 2));

        $attendanceWeeksRange = collect(range(0, 7))->map(fn (int $index) => $now->copy()->subWeeks(7 - $index)->startOfWeek());
        $attendanceRaw = Attendance::where('attended_on', '>=', $attendanceWeeksRange->first()->toDateString())
            ->get(['attended_on'])
            ->groupBy(fn (Attendance $attendance) => $attendance->attended_on->copy()->startOfWeek()->format('Y-m-d'))
            ->map->count();
        $attendanceLabels = $attendanceWeeksRange->map(fn (Carbon $week) => 'Нед ' . $week->format('W'));
        $attendanceValues = $attendanceWeeksRange->map(fn (Carbon $week) => (int) ($attendanceRaw[$week->format('Y-m-d')] ?? 0));

        $statusBreakdown = Enrollment::select('status')->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sectionsWithCounts = Section::withCount(['enrollments as active_enrollments_count' => fn ($query) => $query->where('status', '!=', 'expired')])
            ->with(['direction'])
            ->where('is_active', true)
            ->get();
        $topSections = $sectionsWithCounts->sortByDesc('active_enrollments_count')->take(5);

        $schedules = SectionSchedule::with(['section' => function ($query) {
            $query->where('is_active', true)->withCount(['enrollments as active_enrollments_count' => fn ($q) => $q->where('status', '!=', 'expired')]);
        }])->get()->groupBy('weekday');

        $weekdayNames = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье',
        ];

        $calendarDays = collect(range(0, 13))->map(function (int $offset) use ($now, $schedules, $weekdayNames) {
            $date = $now->copy()->startOfDay()->addDays($offset);
            $weekday = $date->isoWeekday();
            $slots = $schedules->get($weekday, collect())->filter(fn (SectionSchedule $slot) => $slot->section !== null);

            $sections = $slots->map(function (SectionSchedule $slot) {
                $section = $slot->section;
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'direction' => $section->direction?->name,
                    'time' => $slot->starts_at->format('H:i') . ' – ' . $slot->ends_at->format('H:i'),
                    'active_enrollments' => (int) $section->active_enrollments_count,
                ];
            })->values();

            return [
                'date' => $date,
                'weekday' => $weekdayNames[$weekday] ?? $weekday,
                'sections' => $sections,
                'total_children' => $sections->sum('active_enrollments'),
            ];
        });

        return view('dashboard', [
            'metrics' => [
                'totalChildren' => $totalChildren,
                'activeChildren' => $activeChildren,
                'newChildrenThisMonth' => $newChildrenThisMonth,
                'newChildrenLastMonth' => $newChildrenLastMonth,
                'sectionsCount' => $sectionsCount,
                'activeSections' => $activeSections,
                'directionsCount' => $directionsCount,
                'activeEnrollments' => $activeEnrollments,
                'paymentsThisMonth' => $paymentsThisMonth,
                'paymentsLastMonth' => $paymentsLastMonth,
                'avgPaymentThisMonth' => $avgPaymentThisMonth,
                'attendanceThisWeek' => $attendanceThisWeek,
                'attendanceLastWeek' => $attendanceLastWeek,
                'outstandingBalance' => $outstandingBalance,
            ],
            'childrenGrowthChart' => [
                'labels' => $childrenGrowthLabels,
                'values' => $childrenGrowthValues,
            ],
            'paymentsChart' => [
                'labels' => $childrenGrowthLabels,
                'values' => $paymentsTrendValues,
            ],
            'attendanceChart' => [
                'labels' => $attendanceLabels,
                'values' => $attendanceValues,
            ],
            'statusBreakdown' => [
                'labels' => $statusBreakdown->keys()->values(),
                'values' => $statusBreakdown->values(),
            ],
            'topSections' => $topSections,
            'expiringEnrollments' => $expiringEnrollments,
            'recentPayments' => $recentPayments,
            'calendarDays' => $calendarDays,
            'sectionsToday' => $calendarDays->first(),
            'sectionsTomorrow' => $calendarDays->get(1),
        ]);
    }
}
