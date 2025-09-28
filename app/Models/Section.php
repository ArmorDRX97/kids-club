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
        'has_trial',
        'trial_is_free',
        'trial_price',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_trial' => 'boolean',
        'trial_is_free' => 'boolean',
        'trial_price' => 'decimal:2',
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

    public function trialAttendances()
    {
        return $this->hasMany(TrialAttendance::class);
    }

    public function hasChildTrialAttendance(Child $child): bool
    {
        return $this->trialAttendances()->where('child_id', $child->id)->exists();
    }
}
