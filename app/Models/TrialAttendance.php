<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'section_id',
        'attended_on',
        'attended_at',
        'is_free',
        'price',
        'paid_amount',
        'payment_method',
        'payment_comment',
        'marked_by',
    ];

    protected $casts = [
        'attended_on' => 'date',
        'attended_at' => 'datetime',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function isPaid(): bool
    {
        return !$this->is_free && $this->paid_amount >= $this->price;
    }

    public function needsPayment(): bool
    {
        return !$this->is_free && $this->paid_amount < $this->price;
    }
}
