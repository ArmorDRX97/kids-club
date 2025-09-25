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
        $packages = $section->packages()->orderBy('name')->get();
        return view('sections.members.index', compact('section','members','q','packages'));
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
        $addPayload = json_decode($request->input('add_payload', '[]'), true);
        $remove = json_decode($request->input('remove_ids', '[]'), true);

        // Страховка: если decode вернул null — подставляем пустой массив
        $addPayload = is_array($addPayload) ? $addPayload : [];
        $remove = is_array($remove) ? $remove : [];

        // Валидация ID
        $validated = \Validator::make([
            'add_payload' => $addPayload,
            'remove_ids' => $remove,
        ], [
            'add_payload' => ['array'],
            'add_payload.*.child_id' => ['required','integer','exists:children,id'],
            'add_payload.*.package_id' => ['required','integer','exists:packages,id'],
            'remove_ids' => ['array'],
            'remove_ids.*' => ['integer', 'exists:children,id'],
        ])->validate();

        $additions = $validated['add_payload'] ?? [];
        $packageIds = collect($additions)->pluck('package_id')->unique()->all();

        if (!empty($packageIds)) {
            $packages = $section->packages()->whereIn('id', $packageIds)->get()->keyBy('id');
            if ($packages->count() !== count($packageIds)) {
                return back()->with('error', 'Выбран один или несколько пакетов, не принадлежащих секции.');
            }
        } else {
            $packages = collect();
        }

        // Обработка откреплений
        if (!empty($validated['remove_ids'])) {
            Enrollment::where('section_id', $section->id)
                ->whereIn('child_id', $validated['remove_ids'])
                ->update([
                    'expires_at' => now()->subDay(),
                    'visits_left' => 0,
                    'status' => 'expired',
                ]);
        }

        // Обработка прикреплений
        if (!empty($additions)) {
            foreach ($additions as $item) {
                $childId = (int) $item['child_id'];
                $packageId = (int) $item['package_id'];

                // Проверим, нет ли уже активного прикрепления
                $exists = Enrollment::where('child_id', $childId)
                    ->where('section_id', $section->id)
                    ->where(function($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })
                    ->latest('started_at')
                    ->first();

                if ($exists) continue;

                /** @var \App\Models\Package|null $pkg */
                $pkg = $packages[$packageId] ?? null;
                if (!$pkg) {
                    // На всякий случай пропускаем, если пакет не найден в списке секции
                    continue;
                }

                $enr = new Enrollment();
                $enr->child_id = $childId;
                $enr->section_id = $section->id;
                $enr->package_id = $pkg->id;
                $enr->started_at = now()->toDateString();
                $enr->price = $pkg->price;
                $enr->total_paid = 0;
                $enr->status = 'pending';

                if ($pkg->billing_type === 'visits') {
                    $enr->visits_left = $pkg->visits_count;
                    $enr->expires_at = null;
                } elseif ($pkg->billing_type === 'period' && $pkg->days) {
                    $enr->expires_at = now()->addDays($pkg->days);
                    $enr->visits_left = null;
                } else {
                    $enr->visits_left = null;
                }

                $enr->save();
            }
        }

        return redirect()->route('sections.members.index', $section)
            ->with('success', 'Изменения сохранены');
    }

}
