<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'weekday',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'starts_at' => 'datetime:H:i',
        'ends_at' => 'datetime:H:i',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
