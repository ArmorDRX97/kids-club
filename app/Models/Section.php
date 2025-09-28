<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'direction_id',
        'room_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function schedules()
    {
        return $this->hasMany(SectionSchedule::class);
    }

    public function activeSchedulesForWeekday(int $weekday)
    {
        return $this->schedules()->where('weekday', $weekday)->orderBy('starts_at');
    }
}
