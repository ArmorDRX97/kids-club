<?php
namespace App\Http\Controllers;
use App\Models\{Section, Room};
use Illuminate\Http\Request;


class SectionController extends Controller
{
    public function index()
    {
        $parents = Section::whereNull('parent_id')
            ->with([
                'room',
                'children' => function($query){
                    $query->with('room')->withCount(['enrollments','packages']);
                },
            ])
            ->withCount(['enrollments','packages'])
            ->orderBy('name')
            ->get();

        return view('sections.index', compact('parents'));
    }

    public function create()
    {
        $parents = Section::orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        return view('sections.create', compact('parents','rooms'));
    }


    public function store(Request $request)
    {
        $payload = $request->all();

        if ($request->input('schedule_type') === 'monthly') {
            $payload['weekdays'] = [];
            $payload['month_days'] = $this->parseMonthDays($request->input('month_days'));
        } else {
            $payload['month_days'] = [];
            $weekdays = $request->input('weekdays', []);
            $payload['weekdays'] = is_array($weekdays)
                ? collect($weekdays)->map(fn($v) => (int) $v)->unique()->values()->all()
                : [];
        }

        $payload['is_active'] = isset($payload['is_active']) ? (bool) $payload['is_active'] : true;

        $data = \Validator::make($payload, [
            'name'=>['required','string','max:150'],
            'parent_id'=>['nullable','exists:sections,id'],
            'room_id'=>['nullable','exists:rooms,id'],
            'is_active'=>['boolean'],
            'schedule_type'=>['required','in:weekly,monthly'],
            'weekdays'=>['nullable','array'],
            'weekdays.*'=>['integer','between:1,7'],
            'month_days'=>['nullable','array'],
            'month_days.*'=>['integer','between:1,31'],
        ])->validate();

        $section = Section::create($data);
        return redirect()->route('sections.index')->with('success','Секция сохранена');
    }
    public function edit(Section $section)
    {
        $parents = Section::where('id','!=',$section->id)->orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $kidsCount = $section->enrollments()->count();
        return view('sections.edit', compact('section','parents','rooms','kidsCount'));
    }

    public function update(Request $request, Section $section)
    {
        $kidsCount = $section->enrollments()->count();

        // сначала подготовим данные
        $data = $request->all();

        // преобразуем month_days: строка -> массив чисел
        if ($request->schedule_type === 'monthly') {
            $data['month_days'] = $this->parseMonthDays($request->input('month_days'));
            $data['weekdays'] = [];
        } else {
            $weekdays = $request->input('weekdays', []);
            $data['weekdays'] = is_array($weekdays)
                ? collect($weekdays)->map(fn($v) => (int) $v)->unique()->values()->all()
                : [];
            $data['month_days'] = [];
        }

        // чтобы Laravel видел is_active как boolean
        $data['is_active'] = isset($data['is_active']) ? (bool)$data['is_active'] : false;

        // теперь валидируем
        $validated = \Validator::make($data, [
            'name'              => ['required','string','max:150'],
            'parent_id'         => ['nullable','exists:sections,id'],
            'room_id'           => ['nullable','exists:rooms,id'],
            'is_active'         => ['boolean'],
            'schedule_type'     => ['required','in:weekly,monthly'],
            'weekdays'          => ['nullable','array'],
            'weekdays.*'        => ['integer','between:1,7'],
            'month_days'        => ['nullable','array'],
            'month_days.*'      => ['integer','between:1,31'],
        ])->validate();

        // нельзя деактивировать, если есть дети
        if (($validated['is_active'] ?? $section->is_active) == false && $kidsCount > 0) {
            return back()->with('error','Нельзя деактивировать секцию — есть прикреплённые дети');
        }

        // нельзя выбрать саму себя как parent
        if(isset($validated['parent_id']) && (int)$validated['parent_id'] === $section->id) {
            unset($validated['parent_id']);
        }

        $section->update($validated);

        return redirect()->route('sections.index')->with('success','Обновлено');
    }


    public function destroy(Section $section)
    {
        if ($section->enrollments()->exists()) {
            return back()->with('error','Нельзя удалить секцию — есть прикреплённые дети');
        }
        $section->delete();
        return redirect()->route('sections.index')->with('success','Удалено');
}

    /**
     * @param  mixed  $value
     * @return array<int,int>
     */
    protected function parseMonthDays(mixed $value): array
    {
        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
        } elseif (is_array($value)) {
            $parts = $value;
        } else {
            $parts = [];
        }

        $days = collect($parts)
            ->map(fn($item) => (int) $item)
            ->filter(fn($item) => $item >= 1 && $item <= 31)
            ->unique()
            ->values()
            ->all();

        return $days;
    }
}
