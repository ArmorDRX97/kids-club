<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'section_id',
        'section_schedule_id',
        'package_id',
        'started_at',
        'expires_at',
        'visits_left',
        'price',
        'total_paid',
        'status',
    ];

    protected $casts = [
        'started_at' => 'date',
        'expires_at' => 'date',
        'price' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function schedule()
    {
        return $this->belongsTo(SectionSchedule::class, 'section_schedule_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshStatus(): void
    {
        if ($this->expires_at && now()->gt($this->expires_at)) {
            $this->status = 'expired';
        } else {
            if ($this->total_paid >= $this->price) {
                $this->status = 'paid';
            } elseif ($this->total_paid > 0) {
                $this->status = 'partial';
            } else {
                $this->status = 'pending';
            }
        }

        $this->save();
    }
}
