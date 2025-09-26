<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ShiftManager;


class ShiftController extends Controller {
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function start(Request $request){
        $user = $request->user();
        $open = $this->shiftManager->getActiveShift($user);
        if ($open) return back()->with('info','Смена уже открыта.');

        [$shift, $scheduledStart, $scheduledEnd] = $this->shiftManager->openShift($user);

        $response = back()->with('success','Смена начата.');
        if ($shift->started_at->lt($scheduledStart)) {
            $message = $shift->auto_close_enabled
                ? 'Смена начата раньше графика — она автоматически завершится в '.$scheduledEnd->format('H:i').'.'
                : 'Смена начата раньше графика — завершить её можно будет после '.$scheduledEnd->format('H:i').'.';
            $response = $response->with('warning', $message);
        }

        return $response;
    }
    public function stop(Request $request){
        $user = $request->user();
        $shift = $this->shiftManager->getActiveShift($user);
        if (!$shift) return back()->with('info','Нет открытой смены.');

        if (!$shift->auto_close_enabled && $shift->scheduled_end_at && now()->lt($shift->scheduled_end_at)) {
            return back()->with('warning','Смену нельзя завершить раньше '.$shift->scheduled_end_at->format('H:i').'.');
        }

        $this->shiftManager->closeShift($shift);
        return back()->with('success','Смена закрыта.');
    }
}
