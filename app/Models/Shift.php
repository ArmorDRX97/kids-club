<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Shift extends Model {
    use HasFactory;
    protected $fillable = [
        'user_id',
        'started_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'ended_at',
        'duration_min',
        'auto_close_enabled',
        'closed_automatically',
    ];
    protected $casts = [
        'started_at'=>'datetime',
        'scheduled_start_at'=>'datetime',
        'scheduled_end_at'=>'datetime',
        'ended_at'=>'datetime',
        'auto_close_enabled'=>'boolean',
        'closed_automatically'=>'boolean',
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
