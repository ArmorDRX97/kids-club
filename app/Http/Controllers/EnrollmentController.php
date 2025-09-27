<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Package;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EnrollmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'started_at' => ['required', 'date'],
        ]);

        $package = Package::where('id', $data['package_id'])
            ->where('section_id', $data['section_id'])
            ->firstOrFail();

        $enrollment = new Enrollment($data);
        $enrollment->price = $package->price;
        $enrollment->total_paid = 0;
        $enrollment->status = 'pending';

        if ($package->billing_type === 'visits') {
            $enrollment->visits_left = $package->visits_count;
        } elseif ($package->billing_type === 'period' && $package->days) {
            $enrollment->expires_at = Carbon::parse($data['started_at'])->addDays($package->days);
        }

        $enrollment->save();
        $enrollment->load(['child', 'section', 'package']);

        ActivityLogger::log($request->user(), 'child.enrollment_added', $enrollment->child, [
            'section_id' => $enrollment->section_id,
            'section_name' => $enrollment->section?->name,
            'package_id' => $enrollment->package_id,
            'package_name' => $enrollment->package?->name,
            'enrollment_id' => $enrollment->id,
            'started_at' => $enrollment->started_at?->toDateString(),
        ]);

        return back()->with('success', 'Ребёнок прикреплён к пакету.');
    }
}


