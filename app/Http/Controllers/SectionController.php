<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SectionController extends Controller
{
    public function index()
    {
        $directions = Direction::with(['sections' => function ($query) {
            $query->withCount(['enrollments', 'packages'])->with(['room', 'schedules']);
        }])->orderBy('name')->get();

        $orphanSections = Section::whereNull('direction_id')
            ->withCount(['enrollments', 'packages'])
            ->with(['room', 'schedules'])
            ->orderBy('name')
            ->get();

        return view('sections.index', [
            'directions' => $directions,
            'orphanSections' => $orphanSections,
        ]);
    }

    public function create()
    {
        return view('sections.create', [
            'directions' => Direction::orderBy('name')->get(),
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $schedulePayload] = $this->validateSection($request);

        DB::transaction(function () use ($data, $schedulePayload) {
            /** @var Section $section */
            $section = Section::create($data);

            $section->schedules()->createMany($schedulePayload);
        });

        return redirect()->route('sections.index')->with('success', 'Секция создана.');
    }

    public function edit(Section $section)
    {
        $section->load('schedules');

        $lockedScheduleIds = $section->enrollments()
            ->whereNotNull('section_schedule_id')
            ->pluck('section_schedule_id')
            ->unique()
            ->toArray();

        return view('sections.edit', [
            'section' => $section,
            'directions' => Direction::orderBy('name')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'scheduleMatrix' => $section->schedules
                ->groupBy('weekday')
                ->map(fn ($items) => $items->map(fn (SectionSchedule $schedule) => [
                    'id' => $schedule->id,
                    'starts_at' => $schedule->starts_at->format('H:i'),
                    'ends_at' => $schedule->ends_at->format('H:i'),
                ])->values())
                ->toArray(),
            'lockedScheduleIds' => $lockedScheduleIds,
            'enrollmentsCount' => $section->enrollments()->count(),
        ]);
    }

    public function update(Request $request, Section $section)
    {
        [$data, $schedulePayload] = $this->validateSection($request, $section->id);

        DB::transaction(function () use ($section, $data, $schedulePayload) {
            $section->update($data);
            $section->load('schedules');

            $referencedScheduleIds = $section->enrollments()
                ->whereNotNull('section_schedule_id')
                ->pluck('section_schedule_id')
                ->toArray();

            $section->schedules()->whereNotIn('id', $referencedScheduleIds)->delete();
            $section->load('schedules');

            $existingByKey = $section->schedules
                ->keyBy(fn (SectionSchedule $schedule) => $schedule->weekday . '|' . $schedule->starts_at->format('H:i') . '|' . $schedule->ends_at->format('H:i'));

            $newEntries = [];
            foreach ($schedulePayload as $item) {
                $key = $item['weekday'] . '|' . $item['starts_at'] . '|' . $item['ends_at'];
                if (! $existingByKey->has($key)) {
                    $newEntries[] = $item;
                }
            }

            if (! empty($newEntries)) {
                $section->schedules()->createMany($newEntries);
            }
        });

        return redirect()->route('sections.index')->with('success', 'Секция обновлена.');
    }

    public function destroy(Section $section)
    {
        if ($section->enrollments()->exists()) {
            return back()->with('error', 'Нельзя удалить секцию, у которой есть активные записи.');
        }

        $section->delete();

        return redirect()->route('sections.index')->with('success', 'Секция удалена.');
    }

    private function validateSection(Request $request, ?int $sectionId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'direction_id' => ['nullable', 'exists:directions,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'is_active' => ['nullable', 'boolean'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_is_free' => ['nullable', 'boolean'],
            'trial_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['has_trial'] = (bool) ($data['has_trial'] ?? false);
        $data['trial_is_free'] = (bool) ($data['trial_is_free'] ?? true);
        
        // Если пробное занятие отключено, сбрасываем все связанные поля
        if (!$data['has_trial']) {
            $data['trial_is_free'] = true;
            $data['trial_price'] = null;
        }
        
        // Если пробное занятие платное, но цена не указана - ошибка
        if ($data['has_trial'] && !$data['trial_is_free'] && (!$data['trial_price'] || $data['trial_price'] <= 0)) {
            throw ValidationException::withMessages(['trial_price' => 'Укажите цену для платного пробного занятия.']);
        }
        
        // Если пробное занятие бесплатное, обнуляем цену
        if ($data['has_trial'] && $data['trial_is_free']) {
            $data['trial_price'] = null;
        }

        $scheduleInput = $request->input('schedule', []);
        if (! is_array($scheduleInput) || empty($scheduleInput)) {
            throw ValidationException::withMessages(['schedule' => 'Нужно указать расписание секции.']);
        }

        $parsed = [];
        foreach ($scheduleInput as $weekday => $rows) {
            if (! is_numeric($weekday)) {
                continue;
            }
            $weekday = (int) $weekday;
            if ($weekday < 1 || $weekday > 7) {
                continue;
            }

            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $startsAt = Arr::get($row, 'starts_at');
                $endsAt = Arr::get($row, 'ends_at');

                if (! $this->isValidTime($startsAt) || ! $this->isValidTime($endsAt)) {
                    throw ValidationException::withMessages(['schedule' => 'Неверный формат времени. Используйте ЧЧ:ММ.']);
                }

                if (strtotime($startsAt) >= strtotime($endsAt)) {
                    throw ValidationException::withMessages(['schedule' => 'Время начала должно быть меньше времени окончания.']);
                }

                $parsed[] = [
                    'weekday' => $weekday,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ];
            }
        }

        if (empty($parsed)) {
            throw ValidationException::withMessages(['schedule' => 'Добавьте хотя бы один временной слот.']);
        }

        return [$data, $parsed];
    }

    private function isValidTime(?string $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}
