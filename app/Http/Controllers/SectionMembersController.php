<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Package;
use App\Models\Section;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionMembersController extends Controller
{
    public function index(Request $request, Section $section)
    {
        $membersQuery = Enrollment::with(['child', 'package'])
            ->where('section_id', $section->id)
            ->whereHas('child', fn ($query) => $query->where('is_active', true))
            ->latest('started_at');

        $search = trim((string) $request->get('q', ''));

        if ($search !== '') {
            $membersQuery->whereHas('child', function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patronymic', 'like', "%{$search}%")
                    ->orWhere('parent_phone', 'like', "%{$search}%")
                    ->orWhere('parent2_phone', 'like', "%{$search}%")
                    ->orWhere('child_phone', 'like', "%{$search}%");
            });
        }

        $members = $membersQuery->paginate(15)->withQueryString();
        $packages = $section->packages()->orderBy('name')->get();

        $packagesData = $packages->map(fn ($pkg) => $pkg->only(['id', 'name', 'billing_type', 'visits_count', 'days']))->values();

        return view('sections.members.index', [
            'section' => $section,
            'members' => $members,
            'q' => $search,
            'packages' => $packages,
            'packagesData' => $packagesData,
        ]);
    }

    public function search(Request $request, Section $section)
    {
        $search = trim((string) $request->get('q', ''));
        $excludedIds = Enrollment::where('section_id', $section->id)
            ->select('child_id')
            ->distinct()
            ->pluck('child_id');

        $children = Child::active()
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
            ->whereNotIn('id', $excludedIds)
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'patronymic', 'child_phone']);

        return response()->json($children);
    }

    public function store(Request $request, Section $section)
    {
        $addPayload = json_decode($request->input('add_payload', '[]'), true) ?: [];
        $removePayload = json_decode($request->input('remove_ids', '[]'), true) ?: [];

        $validated = Validator::make([
            'add_payload' => $addPayload,
            'remove_ids' => $removePayload,
        ], [
            'add_payload' => ['array'],
            'add_payload.*.child_id' => ['required', 'integer', 'exists:children,id'],
            'add_payload.*.package_id' => ['required', 'integer', 'exists:packages,id'],
            'remove_ids' => ['array'],
            'remove_ids.*' => ['integer', 'exists:children,id'],
        ])->validate();

        $additions = $validated['add_payload'] ?? [];
        $removeIds = collect($validated['remove_ids'] ?? [])->unique()->values()->all();

        $packageIds = collect($additions)->pluck('package_id')->unique()->all();
        $packages = collect();

        if (! empty($packageIds)) {
            $packages = $section->packages()->whereIn('id', $packageIds)->get()->keyBy('id');

            if ($packages->count() !== count($packageIds)) {
                return back()->with('error', 'Выбран пакет, который не относится к этой секции.');
            }
        }

        if (! empty($removeIds)) {
            $removedEnrollments = Enrollment::with(['child', 'package', 'section'])
                ->where('section_id', $section->id)
                ->whereIn('child_id', $removeIds)
                ->get();

            foreach ($removedEnrollments as $enrollment) {
                if ($enrollment->child) {
                    ActivityLogger::log($request->user(), 'child.enrollment_removed', $enrollment->child, [
                        'section_id' => $section->id,
                        'section_name' => $section->name,
                        'package_id' => $enrollment->package_id,
                        'package_name' => $enrollment->package?->name,
                        'enrollment_id' => $enrollment->id,
                    ]);
                }
            }

            Enrollment::where('section_id', $section->id)
                ->whereIn('child_id', $removeIds)
                ->update([
                    'expires_at' => now()->subDay(),
                    'visits_left' => 0,
                    'status' => 'expired',
                ]);
        }

        if (! empty($additions)) {
            foreach ($additions as $item) {
                $childId = (int) $item['child_id'];
                $packageId = (int) $item['package_id'];

                $activeEnrollment = Enrollment::where('child_id', $childId)
                    ->where('section_id', $section->id)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    })
                    ->latest('started_at')
                    ->first();

                if ($activeEnrollment) {
                    continue;
                }

                /** @var Package|null $package */
                $package = $packages[$packageId] ?? null;

                if (! $package) {
                    continue;
                }

                $enrollment = new Enrollment();
                $enrollment->child_id = $childId;
                $enrollment->section_id = $section->id;
                $enrollment->package_id = $package->id;
                $enrollment->started_at = now()->toDateString();
                $enrollment->price = $package->price;
                $enrollment->total_paid = 0;
                $enrollment->status = 'pending';

                if ($package->billing_type === 'visits') {
                    $enrollment->visits_left = $package->visits_count;
                    $enrollment->expires_at = null;
                } elseif ($package->billing_type === 'period' && $package->days) {
                    $enrollment->expires_at = now()->addDays($package->days);
                    $enrollment->visits_left = null;
                } else {
                    $enrollment->visits_left = null;
                }

                $enrollment->save();
                $enrollment->load(['child']);

                if ($enrollment->child) {
                    ActivityLogger::log($request->user(), 'child.enrollment_added', $enrollment->child, [
                        'section_id' => $section->id,
                        'section_name' => $section->name,
                        'package_id' => $package->id,
                        'package_name' => $package->name,
                        'enrollment_id' => $enrollment->id,
                    ]);
                }
            }
        }

        return redirect()
            ->route('sections.members.index', $section)
            ->with('success', 'Изменения сохранены.');
    }
}


