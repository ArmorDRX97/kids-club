<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'section_id',
        'enrollment_id',
        'room_id',
        'attended_on',
        'attended_at',
        'status',
        'source',
        'marked_by',
        'restored_at',
        'restored_by',
        'restored_reason',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
        'attended_on' => 'date',
        'restored_at' => 'datetime',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function restorer()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }
}
