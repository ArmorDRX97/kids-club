<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\{Section, Child, Enrollment, Package};

class SectionMembersController extends Controller
{
    public function index(Request $request, Section $section)
    {
// Текущие прикрепления (последние по ребёнку)
        $enrs = Enrollment::with(['child','package'])
            ->where('section_id',$section->id)
            ->whereHas('child', fn($q)=>$q->where('is_active',true))
            ->latest('started_at');


        $q = trim($request->get('q',''));
        if ($q!=='') {
            $enrs->whereHas('child', function($w) use ($q){
                $w->where('first_name','like',"%$q%")
                    ->orWhere('last_name','like',"%$q%")
                    ->orWhere('patronymic','like',"%$q%")
                    ->orWhere('parent_phone','like',"%$q%")
                    ->orWhere('parent2_phone','like',"%$q%")
                    ->orWhere('child_phone','like',"%$q%");
            });
        }
        $members = $enrs->paginate(15)->withQueryString();
        return view('sections.members.index', compact('section','members','q'));
    }

    public function search(Request $request, Section $section)
    {
        $q = trim($request->get('q',''));
        $existsIds = Enrollment::where('section_id',$section->id)->pluck('child_id')->unique();
        $children = Child::active()
            ->when($q, function($qq) use ($q){
                $qq->where(function($w) use ($q){
                    $w->where('first_name','like',"%$q%")
                        ->orWhere('last_name','like',"%$q%")
                        ->orWhere('patronymic','like',"%$q%")
                        ->orWhere('child_phone','like',"%$q%")
                        ->orWhere('parent_phone','like',"%$q%")
                        ->orWhere('parent2_phone','like',"%$q%");
                });
            })
            ->whereNotIn('id',$existsIds)
            ->orderBy('last_name')
            ->limit(20)->get(['id','first_name','last_name','patronymic','child_phone']);
        return response()->json($children);
    }
    public function store(Request $request, Section $section)
    {
        // Получаем JSON-строки и преобразуем в массивы
        $add = json_decode($request->input('add_ids', '[]'), true);
        $remove = json_decode($request->input('remove_ids', '[]'), true);

        // Страховка: если decode вернул null — подставляем пустой массив
        $add = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        // Валидация ID
        $validated = \Validator::make([
            'add_ids' => $add,
            'remove_ids' => $remove,
        ], [
            'add_ids' => ['array'],
            'add_ids.*' => ['integer', 'exists:children,id'],
            'remove_ids' => ['array'],
            'remove_ids.*' => ['integer', 'exists:children,id'],
        ])->validate();

        // Проверка: для добавления нужен пакет по умолчанию у секции
        $pkg = $section->defaultPackage;
        if (!empty($validated['add_ids']) && !$pkg) {
            return back()->with('error', 'Сначала выберите пакет по умолчанию для секции (в её настройках).');
        }

        // Обработка откреплений
        if (!empty($validated['remove_ids'])) {
            Enrollment::where('section_id', $section->id)
                ->whereIn('child_id', $validated['remove_ids'])
                ->update([
                    'expires_at' => now()->subDay(),
                    'visits_left' => 0,
                    'status' => 'closed',
                ]);
        }

        // Обработка прикреплений
        if (!empty($validated['add_ids'])) {
            foreach ($validated['add_ids'] as $childId) {
                // Проверим, нет ли уже активного прикрепления
                $exists = Enrollment::where('child_id', $childId)
                    ->where('section_id', $section->id)
                    ->where(function($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })
                    ->latest('started_at')
                    ->first();

                if ($exists) continue;

                $enr = new Enrollment();
                $enr->child_id = $childId;
                $enr->section_id = $section->id;
                $enr->package_id = $pkg->id;
                $enr->started_at = now()->toDateString();
                $enr->price = $pkg->price;
                $enr->status = 'pending';

                if ($pkg->type === 'visits') {
                    $enr->visits_left = $pkg->visits_count;
                } elseif ($pkg->type === 'period' && $pkg->days) {
                    $enr->expires_at = now()->addDays($pkg->days);
                }

                $enr->save();
            }
        }

        return redirect()->route('sections.members.index', $section)
            ->with('success', 'Изменения сохранены');
    }

}
