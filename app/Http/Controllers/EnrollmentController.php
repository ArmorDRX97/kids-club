<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Enrollment, Package, Child, Section};
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;


class EnrollmentController extends Controller {
    public function store(Request $request){
        $data = $request->validate([
            'child_id' => ['required','exists:children,id'],
            'section_id' => ['required','exists:sections,id'],
            'package_id' => ['required','exists:packages,id'],
            'started_at' => ['required','date'],
        ]);
        $package = Package::where('id', $data['package_id'])
            ->where('section_id', $data['section_id'])
            ->firstOrFail();

        $enr = new Enrollment($data);
        $enr->price = $package->price;
        $enr->total_paid = 0;
        $enr->status = 'pending';

        if ($package->billing_type === 'visits') {
            $enr->visits_left = $package->visits_count;
        } elseif ($package->billing_type === 'period' && $package->days) {
            $enr->expires_at = Carbon::parse($data['started_at'])->addDays($package->days);
        }

        $enr->save();
        return back()->with('success','Ребёнок прикреплён к пакету.');
    }
}
