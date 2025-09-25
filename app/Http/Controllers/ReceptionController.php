<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Child, Enrollment, Section, Package, Attendance};


class ReceptionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q',''));
        $todayDow = now()->isoWeekday();
        $todayDom = now()->day;


        $sections = Section::where('is_active', true)->where(function($s) use ($todayDow,$todayDom){
            $s->where(function($w) use ($todayDow){
                $w->where('schedule_type','weekly')->whereJsonContains('weekdays', $todayDow);
            })->orWhere(function($w) use ($todayDom){
                $w->where('schedule_type','monthly')->whereJsonContains('month_days', $todayDom);
            });
        })->with(['room','parent'])->orderBy('name')->get();


// Поиск детей (опциональный фильтр на панели сверху)
        $children = collect();
        if ($q !== '') {
            $children = Child::active()->where(function($w) use ($q){
                $w->where('first_name','like',"%$q%")
                    ->orWhere('last_name','like',"%$q%")
                    ->orWhere('patronymic','like',"%$q%")
                    ->orWhere('parent_phone','like',"%$q%")
                    ->orWhere('parent2_phone','like',"%$q%")
                    ->orWhere('child_phone','like',"%$q%");
            })->limit(20)->get();
        }


// Открытая смена текущего пользователя (для таймера)
        $shift = \App\Models\Shift::where('user_id',$request->user()->id)->whereNull('ended_at')->latest('started_at')->first();


        return view('reception.index', compact('sections','q','children','shift'));
    }


    public function mark(Request $request)
    {
        $data = $request->validate([
            'child_id'=>['required','exists:children,id'],
            'section_id'=>['required','exists:sections,id'],
        ]);
        $child = Child::where('id',$data['child_id'])->where('is_active',true)->firstOrFail();
        $enr = Enrollment::where('child_id',$child->id)->where('section_id',$data['section_id'])->latest('started_at')->first();
        if(!$enr) return back()->with('error','Нет активного пакета для этой секции');
        if($enr->expires_at && now()->gt($enr->expires_at)) return back()->with('error','Срок пакета истёк');
        if(!is_null($enr->visits_left) && $enr->visits_left < 1) return back()->with('error','Посещения закончились');


        $today = now()->toDateString();
        $exists = Attendance::where('child_id',$child->id)->where('section_id',$data['section_id'])->where('attended_on',$today)->exists();
        if ($exists) return back()->with('info','Сегодня уже отмечен');


        Attendance::create([
            'child_id'=>$child->id,
            'section_id'=>$data['section_id'],
            'enrollment_id'=>$enr->id,
            'room_id'=>$enr->section->room_id,
            'attended_on'=>$today,
            'attended_at'=>now(),
            'marked_by'=>$request->user()->id,
        ]);
        if(!is_null($enr->visits_left)) { $enr->decrement('visits_left'); }


        return back()->with('success','Отмечено');
    }


    public function renew(Request $request)
    {
// Обёртка может вызывать PaymentsController, но здесь оставим простой редирект на модал в карточке ребёнка
        return back()->with('info','Откройте карточку ребёнка и воспользуйтесь кнопкой «Оплатить/продлить».');
    }
}
