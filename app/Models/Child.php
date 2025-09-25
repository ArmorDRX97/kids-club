<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Child extends Model {
    use HasFactory;
    protected $fillable = [
        'first_name','last_name','patronymic','dob','child_phone','parent_phone','parent2_phone','is_active','notes'
    ];
    protected $casts = [ 'is_active' => 'boolean', 'dob' => 'date' ];
    public function enrollments(){ return $this->hasMany(Enrollment::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
    public function attendances(){ return $this->hasMany(Attendance::class); }
    public function getFullNameAttribute(){
        return trim($this->last_name.' '.$this->first_name.' '.($this->patronymic ?? ''));
    }
    public function scopeActive($q){ return $q->where('is_active', true); }
}
