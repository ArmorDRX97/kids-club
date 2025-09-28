<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Package extends Model {
    use HasFactory;
    protected $fillable = [
        'section_id',
        'name',
        'billing_type',
        'visits_count',
        'days',
        'price',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function section(){ return $this->belongsTo(Section::class); }

    public function enrollments(){ return $this->hasMany(Enrollment::class); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
