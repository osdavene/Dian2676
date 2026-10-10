<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThematicAxis extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'job_profile_id',
        'category',
        'name',
        'description',
        'weight_pct',
    ];

    protected $casts = [
        'weight_pct' => 'decimal:2',
    ];

    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
