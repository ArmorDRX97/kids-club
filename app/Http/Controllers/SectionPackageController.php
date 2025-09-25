<?php

namespace App\Http\Controllers;

use App\Models\{Package, Section};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SectionPackageController extends Controller
{
    public function index(Section $section): View
    {
        $packages = $section->packages()->orderBy('name')->get();

        return view('sections.packages.index', compact('section', 'packages'));
    }

    public function create(Section $section): View
    {
        return view('sections.packages.create', compact('section'));
    }

    public function store(Request $request, Section $section): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['section_id'] = $section->id;
        $section->packages()->create($data);

        return redirect()
            ->route('sections.packages.index', $section)
            ->with('success', 'Пакет добавлен');
    }

    public function edit(Section $section, Package $package): View
    {
        $this->ensureBelongsToSection($section, $package);

        return view('sections.packages.edit', compact('section', 'package'));
    }

    public function update(Request $request, Section $section, Package $package): RedirectResponse
    {
        $this->ensureBelongsToSection($section, $package);

        $data = $this->validatePayload($request);
        $package->update($data);

        return redirect()
            ->route('sections.packages.index', $section)
            ->with('success', 'Пакет обновлён');
    }

    public function destroy(Section $section, Package $package): RedirectResponse
    {
        $this->ensureBelongsToSection($section, $package);

        if ($package->enrollments()->exists()) {
            return back()->with('error', 'Нельзя удалить пакет — есть связанные абонементы.');
        }

        $package->delete();

        return redirect()
            ->route('sections.packages.index', $section)
            ->with('success', 'Пакет удалён');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'billing_type' => ['required', 'in:visits,period'],
            'visits_count' => ['nullable', 'integer', 'min:1'],
            'days' => ['nullable', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        if ($data['billing_type'] === 'visits') {
            $data['days'] = null;
            if (empty($data['visits_count'])) {
                throw ValidationException::withMessages([
                    'visits_count' => ['Укажите количество занятий для пакета.'],
                ]);
            }
        } elseif ($data['billing_type'] === 'period') {
            $data['visits_count'] = null;
            if (empty($data['days'])) {
                throw ValidationException::withMessages([
                    'days' => ['Укажите длительность пакета в днях.'],
                ]);
            }
        }

        return $data;
    }

    protected function ensureBelongsToSection(Section $section, Package $package): void
    {
        if ($package->section_id !== $section->id) {
            abort(404);
        }
    }
}
