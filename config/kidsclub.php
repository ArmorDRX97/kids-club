<?php
return [
    'attendance' => [
        'auto_mark_grace_minutes' => (int) env('ATTENDANCE_AUTO_MARK_GRACE_MINUTES', 15),
        'auto_mark_lookback_days' => (int) env('ATTENDANCE_AUTO_MARK_LOOKBACK_DAYS', 1),
    ],
];

