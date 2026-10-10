<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public $timestamps = false; // El esquema original usa created_at

    protected $fillable = [
        'job_profile_id',
        'is_shared',
        'scope_level',
        'thematic_axis_id',
        'question_type',
        'difficulty',
        'case_text',
        'stem',
        'options',
        'correct_option',
        'legal_basis',
        'explanation_summary',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'is_shared' => 'boolean',
    ];

    /**
     * Scope para obtener preguntas aplicables a un cargo específico
     * (incluye preguntas exclusivas del cargo, preguntas compartidas y transversales)
     */
    public function scopeForCargo($query, $jobId)
    {
        $job = JobProfile::find($jobId);
        $isProfesional = $job && str_contains(strtolower($job->level), 'profesional');
        $isTecnico = $job && str_contains(strtolower($job->level), 'técnico');
        $axisIds = $jobId ? ThematicAxis::where('job_profile_id', $jobId)->pluck('id')->toArray() : [];

        return $query->where(function ($q) use ($jobId, $isProfesional, $isTecnico, $axisIds) {
            $q->where('job_profile_id', $jobId)
              ->orWhere('is_shared', true)
              ->orWhereNull('job_profile_id')
              ->orWhere('scope_level', 'transversal');

            if (!empty($axisIds)) {
                $q->orWhereIn('thematic_axis_id', $axisIds);
            }

            if ($isProfesional) {
                $q->orWhere('scope_level', 'profesional');
            }
            if ($isTecnico) {
                $q->orWhere('scope_level', 'tecnico');
            }
        });
    }

    /**
     * Mapa clave => texto para las opciones (A, B, C, D)
     */
    public function getOptionsMapAttribute(): array
    {
        $map = [];
        if (is_array($this->options)) {
            foreach ($this->options as $opt) {
                if (isset($opt['key']) && isset($opt['text'])) {
                    $map[strtoupper(trim($opt['key']))] = trim($opt['text']);
                }
            }
        }
        return $map;
    }

    public function getOptionAAttribute(): ?string
    {
        return $this->options_map['A'] ?? null;
    }

    public function getOptionBAttribute(): ?string
    {
        return $this->options_map['B'] ?? null;
    }

    public function getOptionCAttribute(): ?string
    {
        return $this->options_map['C'] ?? null;
    }

    public function getOptionDAttribute(): ?string
    {
        return $this->options_map['D'] ?? null;
    }

    public function getRationaleAttribute(): ?string
    {
        return $this->explanation_summary;
    }

    public function getCompetencyTypeAttribute(): string
    {
        return strtoupper($this->question_type ?? 'FUNCIONAL');
    }

    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }

    public function thematicAxis(): BelongsTo
    {
        return $this->belongsTo(ThematicAxis::class);
    }

    public function sessionAnswers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class);
    }
}
