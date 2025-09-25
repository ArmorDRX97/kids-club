<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Teacher extends Model {
    use HasFactory;
    protected $fillable = ['full_name','phone','salary','notes'];
    public function sections(){ return $this->belongsToMany(Section::class); }
}
