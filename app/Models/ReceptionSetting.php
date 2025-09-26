<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_starts_at',
        'shift_ends_at',
        'auto_close_enabled',
    ];

    protected $casts = [
        'auto_close_enabled' => 'boolean',
    ];

    public static function defaults(): array
    {
        return [
            'shift_starts_at' => '09:00:00',
            'shift_ends_at' => '18:00:00',
            'auto_close_enabled' => true,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
