<?php

namespace App\Http\Controllers;

use App\Models\{Child, Enrollment, Section, Package};
use Illuminate\Http\Request;


class ChildController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $showDeleted = (bool)$request->boolean('deleted'); // переключатель
        $children = Child::when(!$showDeleted, fn($qq) => $qq->where('is_active', true))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%")
                        ->orWhere('patronymic', 'like', "%$q%")
                        ->orWhere('child_phone', 'like', "%$q%")
                        ->orWhere('parent_phone', 'like', "%$q%")
                        ->orWhere('parent2_phone', 'like', "%$q%");
                });
            })
            ->orderBy('last_name')->paginate(20)->withQueryString();
        return view('children.index', compact('children', 'q', 'showDeleted'));
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
            'dob' => ['nullable', 'date'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string']
        ]);
        $child = Child::create($data);
        return redirect()->route('children.show', $child)->with('success', 'Ребёнок добавлен');
    }


    public function show(Child $child)
    {
        $child->load(['enrollments.section', 'enrollments.package', 'payments']);
        $sections = Section::orderBy('name')->get();
        $packages = Package::orderBy('section_id')->orderBy('type')->get();
        return view('children.show', compact('child', 'sections', 'packages'));
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
        $child->update($data);
        return redirect()->route('children.show', $child)->with('success', 'Данные обновлены');
    }


    public function deactivate(Request $request, Child $child)
    {
        $hasActivePaid = $child->enrollments()
            ->where(function ($q) {
                $q->where('status', 'paid')->where(function ($w) {
                    $w->whereNotNull('visits_left')->where('visits_left', '>', 0)
                        ->orWhere(function ($w2) {
                            $w2->whereNull('visits_left')->where(function ($w3) {
                                $w3->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                            });
                        });
                });
            })->exists();

        $child->is_active = false;
        $child->save();


        return redirect()->route('children.index', ['deleted' => 1])->with(
            'success', 'Ребёнок помечен как неактивный' . ($hasActivePaid ? ' (у него был активный оплаченный пакет)' : '')
        );
    }


    public function activate(Request $request, Child $child)
    {
        $child->is_active = true;
        $child->save();
        return redirect()->route('children.show', $child)->with('success', 'Ребёнок снова активен');
    }
}
