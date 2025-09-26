<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Section;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $prevMonthStart = $startOfMonth->copy()->subMonth();
        $prevMonthEnd = $startOfMonth->copy()->subDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $prevWeekStart = $startOfWeek->copy()->subWeek();
        $prevWeekEnd = $prevWeekStart->copy()->endOfWeek();
        $monthShort = [1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр', 5 => 'Май', 6 => 'Июн', 7 => 'Июл', 8 => 'Авг', 9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек'];
        $weekdayNames = [1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'];

        $totalChildren = Child::count();
        $activeChildren = Child::active()->count();
        $newChildrenThisMonth = Child::whereBetween('created_at', [$startOfMonth, $now])->count();
        $newChildrenLastMonth = Child::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();

        $sectionsCount = Section::count();
        $activeSections = Section::where('is_active', true)->count();
        $subSectionsCount = Section::whereNotNull('parent_id')->count();
        $rootSectionsCount = $sectionsCount - $subSectionsCount;

        $activeEnrollments = Enrollment::where('status', '!=', 'expired')->count();
        $outstandingBalance = Enrollment::whereIn('status', ['pending', 'partial'])->get(['price', 'total_paid'])
            ->sum(function (Enrollment $enrollment) {
                $remaining = (float) $enrollment->price - (float) $enrollment->total_paid;
                return $remaining > 0 ? $remaining : 0;
            });

        $paymentsThisMonth = (float) Payment::whereBetween('paid_at', [$startOfMonth, $now])->sum('amount');
        $paymentsLastMonth = (float) Payment::whereBetween('paid_at', [$prevMonthStart, $prevMonthEnd])->sum('amount');
        $avgPaymentThisMonth = (float) Payment::whereBetween('paid_at', [$startOfMonth, $now])->avg('amount');

        $attendanceThisWeek = Attendance::whereBetween('attended_on', [$startOfWeek, $now->copy()->endOfWeek()])->count();
        $attendanceLastWeek = Attendance::whereBetween('attended_on', [$prevWeekStart, $prevWeekEnd])->count();

        $recentPayments = Payment::with('child')->orderByDesc('paid_at')->limit(6)->get();

        $expiringEnrollments = Enrollment::with(['child', 'section'])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now->copy()->startOfDay(), $now->copy()->addDays(14)->endOfDay()])
            ->orderBy('expires_at')
            ->limit(10)
            ->get();
        $today = $now->copy()->startOfDay();
        $expiringEnrollments->each(function (Enrollment $enrollment) use ($today) {
            $enrollment->days_left = $today->diffInDays($enrollment->expires_at, false);
        });

        $monthsRange = collect(range(0, 11))->map(function (int $index) use ($now) {
            return $now->copy()->subMonths(11 - $index)->startOfMonth();
        });
        $childGrowthRaw = Child::where('created_at', '>=', $monthsRange->first())
            ->get(['id', 'created_at'])
            ->groupBy(function (Child $child) {
                return $child->created_at->format('Y-m');
            })
            ->map->count();
        $childrenGrowthLabels = $monthsRange->map(function (Carbon $month) use ($monthShort) {
            return ($monthShort[$month->month] ?? $month->format('M')) . ' ' . $month->format('Y');
        });
        $childrenGrowthValues = $monthsRange->map(function (Carbon $month) use ($childGrowthRaw) {
            return (int) ($childGrowthRaw[$month->format('Y-m')] ?? 0);
        });

        $paymentsRaw = Payment::where('paid_at', '>=', $monthsRange->first())
            ->get(['amount', 'paid_at'])
            ->groupBy(function (Payment $payment) {
                return $payment->paid_at->format('Y-m');
            })
            ->map(function ($group) {
                return $group->sum(function (Payment $payment) {
                    return (float) $payment->amount;
                });
            });
        $paymentsTrendValues = $monthsRange->map(function (Carbon $month) use ($paymentsRaw) {
            return round($paymentsRaw[$month->format('Y-m')] ?? 0, 2);
        });

        $attendanceWeeksRange = collect(range(0, 7))->map(function (int $index) use ($now) {
            return $now->copy()->subWeeks(7 - $index)->startOfWeek();
        });
        $attendanceRaw = Attendance::where('attended_on', '>=', $attendanceWeeksRange->first())
            ->get(['attended_on'])
            ->groupBy(function (Attendance $attendance) {
                return $attendance->attended_on->copy()->startOfWeek()->format('Y-m-d');
            })
            ->map->count();
        $attendanceLabels = $attendanceWeeksRange->map(function (Carbon $week) {
            return 'Нед ' . $week->format('W');
        });
        $attendanceValues = $attendanceWeeksRange->map(function (Carbon $week) use ($attendanceRaw) {
            return (int) ($attendanceRaw[$week->format('Y-m-d')] ?? 0);
        });

        $statusBreakdown = Enrollment::select('status')->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sections = Section::withCount(['enrollments as active_enrollments_count' => function ($query) {
            $query->where('status', '!=', 'expired');
        }])->where('is_active', true)->get();

        $calendarDays = collect(range(0, 13))->map(function (int $offset) use ($sections, $weekdayNames, $now) {
            $date = $now->copy()->startOfDay()->addDays($offset);
            $sectionsForDay = $sections->filter(function (Section $section) use ($date) {
                return $this->sectionRunsOnDate($section, $date);
            })->map(function (Section $section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'active_enrollments' => (int) $section->active_enrollments_count,
                ];
            })->values();

            $totalChildren = $sectionsForDay->sum('active_enrollments');

            return [
                'date' => $date,
                'weekday' => $weekdayNames[$date->isoWeekday()] ?? $date->format('l'),
                'sections' => $sectionsForDay->toArray(),
                'total_children' => $totalChildren,
            ];
        })->values();

        $sectionsToday = $calendarDays->first();
        $sectionsTomorrow = $calendarDays->get(1);

        $topSections = $sections->sortByDesc('active_enrollments_count')->take(5);

        return view('dashboard', [
            'metrics' => [
                'totalChildren' => $totalChildren,
                'activeChildren' => $activeChildren,
                'newChildrenThisMonth' => $newChildrenThisMonth,
                'newChildrenLastMonth' => $newChildrenLastMonth,
                'sectionsCount' => $sectionsCount,
                'activeSections' => $activeSections,
                'rootSectionsCount' => $rootSectionsCount,
                'subSectionsCount' => $subSectionsCount,
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
            'sectionsToday' => $sectionsToday,
            'sectionsTomorrow' => $sectionsTomorrow,
        ]);
    }

    protected function sectionRunsOnDate(Section $section, Carbon $date): bool
    {
        if (empty($section->schedule_type)) {
            return false;
        }

        if ($section->schedule_type === 'weekly') {
            $weekdays = $section->weekdays ?? [];
            return in_array($date->isoWeekday(), $weekdays, true);
        }

        if ($section->schedule_type === 'monthly') {
            $monthDays = $section->month_days ?? [];
            return in_array($date->day, $monthDays, true);
        }

        return false;
    }
}
