<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Package extends Model {
    use HasFactory;
    protected $fillable = ['section_id','type','visits_count','days','price','is_active'];
    public function section(){ return $this->belongsTo(Section::class); }
}
