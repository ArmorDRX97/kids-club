<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Enrollment, Package, Child, Section};
use Illuminate\Validation\Rule;


class EnrollmentController extends Controller {
    public function store(Request $request){
        $data = $request->validate([
            'child_id' => ['required','exists:children,id'],
            'section_id' => ['required','exists:sections,id'],
            'package_id' => ['required','exists:packages,id'],
            'started_at' => ['required','date'],
        ]);
        $package = Package::findOrFail($data['package_id']);
        $enr = new Enrollment($data);
        $enr->price = $package->price;
        if ($package->type === 'visits') $enr->visits_left = $package->visits_count;
        if ($package->type === 'period' && $package->days) $enr->expires_at = now()->parse($data['started_at'])->addDays($package->days);
        $enr->save();
        return back()->with('success','Ребёнок прикреплён к пакету.');
    }
}
