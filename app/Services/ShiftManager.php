<?php

namespace App\Services;

use App\Models\ReceptionSetting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;

class ShiftManager
{
    public function getSetting(User $user): ReceptionSetting
    {
        return $user->receptionSetting()->firstOrCreate([], ReceptionSetting::defaults());
    }

    public function getActiveShift(User $user, bool $sync = true): ?Shift
    {
        /** @var Shift|null $shift */
        $shift = Shift::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($shift && $sync) {
            $shift = $this->syncAutoClosure($shift);
            if ($shift->ended_at) {
                return null;
            }
        }

        return $shift;
    }

    public function openShift(User $user): array
    {
        $setting = $this->getSetting($user);
        $now = Carbon::now();
        $scheduledStart = Carbon::parse($now->toDateString() . ' ' . $setting->shift_starts_at, $now->getTimezone());
        $scheduledEnd = Carbon::parse($now->toDateString() . ' ' . $setting->shift_ends_at, $now->getTimezone());

        if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
            $scheduledEnd->addDay();
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'started_at' => $now,
            'scheduled_start_at' => $scheduledStart,
            'scheduled_end_at' => $scheduledEnd,
            'auto_close_enabled' => $setting->auto_close_enabled,
        ]);

        return [$shift, $scheduledStart, $scheduledEnd];
    }

    public function closeShift(Shift $shift, ?Carbon $moment = null, bool $auto = false): Shift
    {
        $moment ??= Carbon::now();
        $shift->ended_at = $moment;
        $shift->duration_min = $shift->started_at->diffInMinutes($moment);
        $shift->closed_automatically = $auto;
        $shift->save();

        return $shift;
    }

    public function syncAutoClosure(Shift $shift): Shift
    {
        if (
            $shift->auto_close_enabled
            && $shift->scheduled_end_at
            && !$shift->ended_at
            && Carbon::now()->greaterThanOrEqualTo($shift->scheduled_end_at)
        ) {
            $this->closeShift($shift, $shift->scheduled_end_at, true);
            $shift->refresh();
        }

        return $shift;
    }
}
