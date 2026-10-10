<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ThematicAxis;
use App\Models\Question;
use App\Models\JobProfile;
use Illuminate\Support\Facades\File;

class RestoreBackupCommand extends Command
{
    protected $signature = 'dian:restore-backup {file? : Ruta del archivo JSON de backup} {--overwrite : Sobrescribir preguntas existentes}';
    protected $description = 'Restaura o importa un archivo de backup total JSON en la base de datos';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $overwrite = (bool) $this->option('overwrite');

        if (!$filePath) {
            // Buscar el último archivo en storage/app/backups/
            $backupDir = storage_path('app/backups');
            $files = glob($backupDir . '/*.json');
            if (empty($files)) {
                $this->error("No se especificó un archivo y no hay backups en {$backupDir}");
                return Command::FAILURE;
            }
            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            $filePath = $files[0];
            $this->info("Usando el archivo de backup más reciente: " . basename($filePath));
        }

        if (!File::exists($filePath)) {
            $this->error("El archivo no existe: {$filePath}");
            return Command::FAILURE;
        }

        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            $this->error("El archivo no es un JSON válido o está corrupto.");
            return Command::FAILURE;
        }

        // Si es una lista directa de preguntas
        if (isset($data[0]['stem'])) {
            $data = ['questions' => $data];
        }

        $this->info("Iniciando restauración...");

        // 1. Restaurar / Verificar Ejes Temáticos
        $axisMapByName = [];
        $axisMapById = [];
        $axesCount = 0;

        if (!empty($data['thematic_axes'])) {
            foreach ($data['thematic_axes'] as $ax) {
                $axis = ThematicAxis::firstOrCreate(
                    ['name' => $ax['name']],
                    [
                        'category' => $ax['category'] ?? 'funcional_especifica',
                        'description' => $ax['description'] ?? null,
                        'weight_pct' => $ax['weight_pct'] ?? 10.00,
                        'job_profile_id' => $ax['job_profile_id'] ?? 1,
                    ]
                );
                $axisMapByName[$ax['name']] = $axis->id;
                if (isset($ax['id'])) {
                    $axisMapById[$ax['id']] = $axis->id;
                }
                $axesCount++;
            }
            $this->info("Ejes temáticos procesados: {$axesCount}");
        }

        // 2. Restaurar Preguntas
        $questionsList = $data['questions'] ?? [];
        if (empty($questionsList)) {
            $this->warn("No se encontraron preguntas en el archivo de backup.");
            return Command::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($questionsList as $q) {
            $stem = trim($q['stem'] ?? '');
            if (!$stem) continue;

            $axisId = null;
            if (!empty($q['thematic_axis_name']) && isset($axisMapByName[$q['thematic_axis_name']])) {
                $axisId = $axisMapByName[$q['thematic_axis_name']];
            } elseif (!empty($q['thematic_axis_id']) && isset($axisMapById[$q['thematic_axis_id']])) {
                $axisId = $axisMapById[$q['thematic_axis_id']];
            } elseif (!empty($q['thematic_axis_id'])) {
                $axisId = $q['thematic_axis_id'];
            }

            $options = $q['options'] ?? null;
            if (!$options && isset($q['option_a'])) {
                $options = [
                    ['key' => 'A', 'text' => $q['option_a'], 'is_correct' => ($q['correct_option'] ?? '') === 'A'],
                    ['key' => 'B', 'text' => $q['option_b'], 'is_correct' => ($q['correct_option'] ?? '') === 'B'],
                    ['key' => 'C', 'text' => $q['option_c'], 'is_correct' => ($q['correct_option'] ?? '') === 'C'],
                ];
                if (!empty($q['option_d'])) {
                    $options[] = ['key' => 'D', 'text' => $q['option_d'], 'is_correct' => ($q['correct_option'] ?? '') === 'D'];
                }
            }

            $existing = Question::where('stem', $stem)->first();
            if ($existing) {
                if ($overwrite) {
                    $existing->update([
                        'thematic_axis_id' => $axisId ?? $existing->thematic_axis_id,
                        'case_text' => $q['case_text'] ?? $existing->case_text,
                        'options' => $options ?? $existing->options,
                        'correct_option' => $q['correct_option'] ?? $existing->correct_option,
                        'legal_basis' => $q['legal_basis'] ?? $existing->legal_basis,
                        'explanation_summary' => $q['explanation_summary'] ?? ($q['rationale'] ?? $existing->explanation_summary),
                        'question_type' => $q['question_type'] ?? $existing->question_type,
                        'difficulty' => $q['difficulty'] ?? $existing->difficulty,
                        'is_shared' => $q['is_shared'] ?? $existing->is_shared,
                        'scope_level' => $q['scope_level'] ?? $existing->scope_level,
                        'is_active' => $q['is_active'] ?? $existing->is_active,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Question::create([
                    'job_profile_id' => $q['job_profile_id'] ?? null,
                    'thematic_axis_id' => $axisId,
                    'case_text' => $q['case_text'] ?? null,
                    'stem' => $stem,
                    'options' => $options,
                    'correct_option' => $q['correct_option'] ?? 'A',
                    'explanation_summary' => $q['explanation_summary'] ?? ($q['rationale'] ?? null),
                    'legal_basis' => $q['legal_basis'] ?? null,
                    'question_type' => $q['question_type'] ?? 'situacional',
                    'difficulty' => $q['difficulty'] ?? 'Media',
                    'is_active' => $q['is_active'] ?? true,
                    'is_shared' => $q['is_shared'] ?? true,
                    'scope_level' => $q['scope_level'] ?? 'transversal',
                ]);
                $inserted++;
            }
        }

        $this->info("Nuevas preguntas insertadas: {$inserted}");
        $this->info("Preguntas actualizadas: {$updated}");
        $this->info("Preguntas omitidas (ya existían): {$skipped}");
        $this->info("Total preguntas en base de datos: " . Question::count());

        // Actualizar archivos de seed en database/seeders/data
        $this->call('dian:export-seed');

        return Command::SUCCESS;
    }
}
