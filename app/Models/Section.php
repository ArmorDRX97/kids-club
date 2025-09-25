<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Section extends Model {
    use HasFactory;
    protected $fillable = ['name','parent_id','room_id','is_active','schedule_type','weekdays','month_days'];
    protected $casts = [ 'is_active'=>'boolean', 'weekdays'=>'array', 'month_days'=>'array' ];
    public function parent(){ return $this->belongsTo(Section::class,'parent_id'); }
    public function children(){ return $this->hasMany(Section::class,'parent_id'); }
    public function room(){ return $this->belongsTo(Room::class); }
    public function packages(){ return $this->hasMany(Package::class); }
    public function teachers(){ return $this->belongsToMany(Teacher::class); }
    public function enrollments(){ return $this->hasMany(Enrollment::class); }


    public function isScheduledToday(): bool {
        $todayDow = (int) now()->isoWeekday(); // 1..7
        $todayDom = (int) now()->day; // 1..31
        if ($this->schedule_type === 'weekly') {
            return in_array($todayDow, $this->weekdays ?? [], true);
        }
        return in_array($todayDom, $this->month_days ?? [], true);
    }

    public function defaultPackage(){ return $this->belongsTo(Package::class,'default_package_id'); }
}
