<?php

namespace App\Console\Commands;

use App\Models\JobProfile;
use App\Models\Question;
use App\Models\ThematicAxis;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database {--keep=14 : Número de copias de seguridad históricas a conservar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un snapshot JSON completo del banco de preguntas, ejes temáticos, cargos y configuración';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando copia de seguridad de la base de datos...');

        $jobs = JobProfile::all();
        $axes = ThematicAxis::with('jobProfile')->get();
        $questions = Question::with(['jobProfile', 'thematicAxis'])->get();
        $users = User::select('id', 'name', 'email', 'role', 'target_cargo', 'job_profile_id', 'created_at')->get();

        $payload = [
            'metadata' => [
                'sistema' => 'Simulador Oficial Concurso DIAN 2676 — CNSC',
                'tipo_backup' => 'BACKUP_PROGRAMADO_SISTEMA_Y_BANCO',
                'fecha_generacion' => now()->toIso8601String(),
                'version_motor' => '1.0.0-PRO',
                'total_cargos' => $jobs->count(),
                'total_ejes' => $axes->count(),
                'total_preguntas' => $questions->count(),
                'total_usuarios' => $users->count(),
            ],
            'job_profiles' => $jobs,
            'thematic_axes' => $axes,
            'questions' => $questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'job_profile_id' => $q->job_profile_id,
                    'job_profile_name' => $q->jobProfile->name ?? null,
                    'is_shared' => $q->is_shared,
                    'scope_level' => $q->scope_level,
                    'thematic_axis_id' => $q->thematic_axis_id,
                    'thematic_axis_name' => $q->thematicAxis->name ?? null,
                    'question_type' => $q->question_type,
                    'difficulty' => $q->difficulty,
                    'case_text' => $q->case_text,
                    'stem' => $q->stem,
                    'options' => $q->options,
                    'correct_option' => $q->correct_option,
                    'legal_basis' => $q->legal_basis,
                    'explanation_summary' => $q->explanation_summary,
                    'is_active' => $q->is_active,
                ];
            }),
            'users_metadata' => $users,
        ];

        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'dian2676_backup_auto_'.now()->format('Y-m-d_His').'.json';
        $fullPath = $backupDir.'/'.$filename;

        File::put($fullPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✓ Copia de seguridad guardada exitosamente: {$filename}");
        $this->info("  Total de preguntas respaldadas: {$questions->count()}");
        $this->info("  Total de ejes respaldados: {$axes->count()}");

        // Limpieza de backups antiguos según opción --keep
        $keep = (int) $this->option('keep');
        if ($keep > 0) {
            $existing = glob($backupDir.'/*.json');
            if (count($existing) > $keep) {
                usort($existing, function ($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });

                $toRemove = array_slice($existing, $keep);
                foreach ($toRemove as $oldFile) {
                    File::delete($oldFile);
                    $this->line('  Eliminado backup antiguo: '.basename($oldFile));
                }
            }
        }

        return Command::SUCCESS;
    }
}
