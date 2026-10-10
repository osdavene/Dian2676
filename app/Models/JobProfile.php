<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobProfile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'level',
        'grade',
        'process',
        'subprocess',
        'purpose',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function thematicAxes(): HasMany
    {
        return $this->hasMany(ThematicAxis::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
