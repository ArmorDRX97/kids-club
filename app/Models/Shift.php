<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Shift extends Model {
    use HasFactory;
    protected $fillable = ['user_id','started_at','ended_at','duration_min'];
    protected $casts = ['started_at'=>'datetime','ended_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
}
