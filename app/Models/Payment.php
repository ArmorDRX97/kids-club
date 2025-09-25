<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Payment extends Model {
    use HasFactory;
    protected $fillable = ['enrollment_id','child_id','amount','paid_at','method','comment','user_id'];
    protected $casts = [ 'paid_at' => 'datetime', 'amount' => 'decimal:2' ];
    public function enrollment(){ return $this->belongsTo(Enrollment::class); }
    public function child(){ return $this->belongsTo(Child::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
