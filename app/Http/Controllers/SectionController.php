<?php
namespace App\Http\Controllers;
use App\Models\Package;
use App\Models\{Section, Room};
use Illuminate\Http\Request;


class SectionController extends Controller
{
    public function index()
    {
        $parents = Section::whereNull('parent_id')
            ->with(['children','room'])
            ->orderBy('name')->get();
// Для каждого — посчитаем детей (через enrollments)
        $counts = [];
        foreach (Section::withCount(['enrollments'])->get() as $s) {
            $counts[$s->id] = $s->enrollments_count;
        }
        return view('sections.index', compact('parents','counts'));
    }

    public function create()
    {
        $parents = Section::orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $packages = Package::with('section')->orderBy('section_id')->get(); // для выбора сразу, но фильтровать будем на фронте
        return view('sections.create', compact('parents','rooms','packages'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>['required','string','max:150'],
            'parent_id'=>['nullable','exists:sections,id'],
            'room_id'=>['nullable','exists:rooms,id'],
            'is_active'=>['nullable','boolean'],
            'schedule_type'=>['required','in:weekly,monthly'],
            'weekdays'=>['nullable','array'],
            'weekdays.*'=>['integer','between:1,7'],
            'month_days'=>['nullable','array'],
            'month_days.*'=>['integer','between:1,31'],
            'default_package_id'=>['nullable','exists:packages,id'],
        ]);
        $data['is_active'] = isset($data['is_active']) ? (bool)$data['is_active'] : true;
        $section = Section::create($data);
// если указан дефолтный пакет — он должен принадлежать этой секции
        if (!empty($data['default_package_id'])) {
            $pkg = Package::find($data['default_package_id']);
            if ($pkg && $pkg->section_id !== $section->id) {
                return back()->withInput()->with('error','Выбранный пакет не принадлежит секции');
            }
        }
        return redirect()->route('sections.index')->with('success','Секция сохранена');
    }
    public function edit(Section $section)
    {
        $parents = Section::where('id','!=',$section->id)->orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $kidsCount = $section->enrollments()->count();
        $packages = Package::where('section_id',$section->id)->orderBy('id')->get(); // пакеты только этой секции
        return view('sections.edit', compact('section','parents','rooms','kidsCount','packages'));
    }

    public function update(Request $request, Section $section)
    {
        $kidsCount = $section->enrollments()->count();

        // сначала подготовим данные
        $data = $request->all();

        // преобразуем month_days: строка -> массив чисел
        if ($request->schedule_type === 'monthly' && !empty($data['month_days'])) {
            if (is_string($data['month_days'])) {
                $days = array_filter(array_map('trim', explode(',', $data['month_days'])));
                $data['month_days'] = array_map('intval', $days);
            }
        } else {
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
            'default_package_id'=> ['nullable','exists:packages,id'],
        ])->validate();

        // нельзя деактивировать, если есть дети
        if (($validated['is_active'] ?? $section->is_active) == false && $kidsCount > 0) {
            return back()->with('error','Нельзя деактивировать секцию — есть прикреплённые дети');
        }

        // нельзя выбрать саму себя как parent
        if(isset($validated['parent_id']) && (int)$validated['parent_id'] === $section->id) {
            unset($validated['parent_id']);
        }

        // проверяем пакет по умолчанию
        if (!empty($validated['default_package_id'])) {
            $pkg = Package::find($validated['default_package_id']);
            if (!$pkg || $pkg->section_id !== $section->id) {
                return back()->withInput()->with('error','Выбранный пакет должен принадлежать текущей секции');
            }
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
}
