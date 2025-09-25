<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Shift;


class ShiftController extends Controller {
    public function start(Request $request){
        $user = $request->user();
        $open = Shift::where('user_id',$user->id)->whereNull('ended_at')->first();
        if ($open) return back()->with('info','Смена уже открыта.');
        Shift::create(['user_id'=>$user->id,'started_at'=>now()]);
        return back()->with('success','Смена начата.');
    }
    public function stop(Request $request){
        $user = $request->user();
        $shift = Shift::where('user_id',$user->id)->whereNull('ended_at')->latest('started_at')->first();
        if (!$shift) return back()->with('info','Нет открытой смены.');
        $shift->ended_at = now();
        $shift->duration_min = $shift->started_at->diffInMinutes($shift->ended_at);
        $shift->save();
        return back()->with('success','Смена закрыта.');
    }
}
