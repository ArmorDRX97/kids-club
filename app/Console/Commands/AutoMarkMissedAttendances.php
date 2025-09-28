<?php

namespace App\Console\Commands;

use App\Services\AttendanceAutoMarker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoMarkMissedAttendances extends Command
{
    protected $signature = 'attendance:auto-miss {--date=} {--lookback=}';

    protected $description = 'Mark missed attendances automatically for schedules that have already passed.';

    public function __construct(private AttendanceAutoMarker $autoMarker)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateOption = $this->option('date');
        $lookback = $this->option('lookback');
        $reference = $dateOption ? Carbon::parse($dateOption) : now();
        $processed = $this->autoMarker->markMissedUpTo($reference, $lookback !== null ? (int) $lookback : null);
        $this->info("Processed {$processed} missed attendances.");
        return self::SUCCESS;
    }
}

