<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $showInactive = (bool) $request->boolean('deleted');

        $children = Child::query()
            ->when(! $showInactive, fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($childQuery) use ($search) {
                    $childQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patronymic', 'like', "%{$search}%")
                        ->orWhere('child_phone', 'like', "%{$search}%")
                        ->orWhere('parent_phone', 'like', "%{$search}%")
                        ->orWhere('parent2_phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('children.index', [
            'children' => $children,
            'q' => $search,
            'showDeleted' => $showInactive,
        ]);
    }

    public function create()
    {
        return view('children.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'patronymic' => ['nullable', 'string', 'max:120'],
            'dob' => ['nullable', 'date'],
            'child_phone' => ['nullable', 'string', 'max:40'],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'parent2_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $child = Child::create($data);

        ActivityLogger::log($request->user(), 'child.created', $child, [
            'name' => $child->full_name,
        ]);

        return redirect()->route('children.show', $child)->with('success', 'Карточка ребёнка создана.');
    }

    public function show(Child $child)
    {
        $child->load([
            'enrollments' => fn ($query) => $query->with(['section.direction', 'package', 'schedule'])->latest('started_at'),
            'payments' => fn ($query) => $query->with(['enrollment.section', 'enrollment.package', 'user'])->latest('paid_at'),
        ]);

        $history = $child->activityLogs()->with('user')->latest()->get();

        $availableSections = Section::with([
                'direction:id,name',
                'schedules',
                'packages' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $weekdayMap = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье',
        ];

        $sectionsPayload = $availableSections->map(function (Section $section) use ($weekdayMap) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'direction' => $section->direction?->name,
                'schedules' => $section->schedules
                    ->sortBy(fn (SectionSchedule $schedule) => $schedule->weekday * 10000 + (int) $schedule->starts_at->format('Hi'))
                    ->map(fn (SectionSchedule $schedule) => [
                        'id' => $schedule->id,
                        'weekday' => $schedule->weekday,
                        'label' => $this->humanScheduleLabel($schedule->weekday, $schedule->starts_at->format('H:i'), $schedule->ends_at->format('H:i'), $weekdayMap),
                    ])->values(),
                'packages' => $section->packages->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'billing_type' => $package->billing_type,
                    'visits_count' => $package->visits_count,
                    'days' => $package->days,
                    'price' => (float) $package->price,
                ])->values(),
            ];
        });

        $outstandingEnrollments = $child->enrollments->filter(fn ($enrollment) => $enrollment->status !== 'paid');
        $totalDebt = $outstandingEnrollments->sum(fn ($enrollment) => max(0, ($enrollment->price ?? 0) - ($enrollment->total_paid ?? 0)));

        return view('children.show', [
            'child' => $child,
            'history' => $history,
            'sectionsPayload' => $sectionsPayload,
            'outstandingEnrollments' => $outstandingEnrollments,
            'totalDebt' => $totalDebt,
        ]);
    }

    public function edit(Child $child)
    {
        return view('children.edit', compact('child'));
    }

    public function update(Request $request, Child $child)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'patronymic' => ['nullable', 'string', 'max:120'],
            'dob' => ['nullable', 'date'],
            'child_phone' => ['nullable', 'string', 'max:40'],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'parent2_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);

        $child->fill($data);

        if (! $child->isDirty()) {
            return redirect()->route('children.show', $child)->with('info', 'Изменений не обнаружено.');
        }

        $changes = [];
        foreach ($child->getDirty() as $key => $new) {
            $old = $child->getOriginal($key);
            if ($new instanceof Carbon) {
                $new = $new->toDateString();
            }
            if ($old instanceof Carbon) {
                $old = $old->toDateString();
            }
            $changes[$key] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        $child->save();

        ActivityLogger::log($request->user(), 'child.updated', $child, [
            'changes' => $changes,
        ]);

        return redirect()->route('children.show', $child)->with('success', 'Данные обновлены.');
    }

    public function destroy(Request $request, Child $child)
    {
        ActivityLogger::log($request->user(), 'child.deleted', $child, [
            'name' => $child->full_name,
        ]);

        $child->delete();

        return redirect()->route('children.index')->with('success', 'Карточка ребёнка удалена.');
    }

    public function deactivate(Request $request, Child $child)
    {
        $hasActivePaid = $child->enrollments()
            ->where(function ($query) {
                $query->where('status', 'paid')->where(function ($sub) {
                    $sub->whereNotNull('visits_left')->where('visits_left', '>', 0)
                        ->orWhere(function ($inner) {
                            $inner->whereNull('visits_left')->where(function ($dates) {
                                $dates->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                            });
                        });
                });
            })
            ->exists();

        $child->is_active = false;
        $child->save();

        ActivityLogger::log($request->user(), 'child.deactivated', $child, [
            'had_active_package' => $hasActivePaid,
        ]);

        return redirect()->route('children.index', ['deleted' => 1])->with('success', 'Карточка ребёнка временно отключена.');
    }

    public function activate(Request $request, Child $child)
    {
        $child->is_active = true;
        $child->save();

        ActivityLogger::log($request->user(), 'child.activated', $child);

        return redirect()->route('children.show', $child)->with('success', 'Карточка ребёнка снова активна.');
    }

    protected function humanScheduleLabel(int $weekday, string $startsAt, string $endsAt, array $weekdayMap): string
    {
        $day = $weekdayMap[$weekday] ?? $weekday;

        return sprintf('%s %s – %s', $day, $startsAt, $endsAt);
    }
}
