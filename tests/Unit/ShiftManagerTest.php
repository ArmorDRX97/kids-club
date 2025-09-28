<?php

namespace Tests\Unit;

use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_shift_after_scheduled_end_rolls_to_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-27 23:30:00'));

        $user = User::factory()->create();
        $manager = app(ShiftManager::class);

        [$shift, $scheduledStart, $scheduledEnd] = $manager->openShift($user);

        $this->assertSame('2025-09-28', $scheduledStart->toDateString());
        $this->assertSame('2025-09-28', $scheduledEnd->toDateString());
        $this->assertTrue($scheduledEnd->greaterThan($scheduledStart));
        $this->assertTrue($scheduledEnd->greaterThan(Carbon::now()));
        $this->assertTrue($shift->scheduled_end_at->equalTo($scheduledEnd));

        Carbon::setTestNow();
    }

    public function test_auto_closure_never_sets_end_before_start(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-28 20:00:00'));

        $user = User::factory()->create();

        $shift = Shift::create([
            'user_id' => $user->id,
            'started_at' => Carbon::parse('2025-09-27 23:00:00'),
            'scheduled_start_at' => Carbon::parse('2025-09-27 09:00:00'),
            'scheduled_end_at' => Carbon::parse('2025-09-27 18:00:00'),
            'auto_close_enabled' => true,
        ]);

        $manager = app(ShiftManager::class);
        $manager->syncAutoClosure($shift);

        $shift->refresh();

        $this->assertNotNull($shift->ended_at);
        $this->assertTrue($shift->ended_at->greaterThan($shift->started_at));
        $this->assertGreaterThan(0, $shift->duration_min);
        $this->assertTrue($shift->closed_automatically);
        $this->assertSame('2025-09-28 18:00:00', $shift->ended_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }
}
