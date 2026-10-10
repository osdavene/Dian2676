<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\JobProfile;
use App\Models\ThematicAxis;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class DianSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Perfiles de Cargo Oficiales
        $analista3 = JobProfile::firstOrCreate(
            ['code' => 'AT-FL-2011'],
            [
                'name' => 'Analista III',
                'level' => 'Nivel Técnico',
                'grade' => '03',
                'process' => 'MISIONAL: Cumplimiento de obligaciones tributarias, aduaneras y cambiarias',
                'subprocess' => 'Fiscalización y liquidación',
                'purpose' => 'Ejecutar actividades técnicas y administrativas de apoyo dirigidas a la verificación del cumplimiento de obligaciones tributarias, aduaneras y cambiarias, detección de LA/FT, de acuerdo con la normativa vigente.',
                'is_active' => true,
            ]
        );

        $analista2 = JobProfile::firstOrCreate(
            ['code' => 'AT-FL-2010'],
            [
                'name' => 'Analista II',
                'level' => 'Nivel Técnico',
                'grade' => '02',
                'process' => 'MISIONAL: Cumplimiento de obligaciones tributarias, aduaneras y cambiarias',
                'subprocess' => 'Fiscalización y liquidación',
                'purpose' => 'Apoyar técnicamente las actividades operativas y de registro en los procesos de fiscalización tributaria y aduanera.',
                'is_active' => true,
            ]
        );

        $gestor1 = JobProfile::firstOrCreate(
            ['code' => 'PF-FL-3011'],
            [
                'name' => 'Gestor I',
                'level' => 'Nivel Profesional',
                'grade' => '01',
                'process' => 'MISIONAL: Fiscalización y liquidación',
                'subprocess' => 'Determinación de tributos',
                'purpose' => 'Adelantar investigaciones tributarias y proyectar actos administrativos de determinación y sanción.',
                'is_active' => true,
            ]
        );

        $gestor3 = JobProfile::firstOrCreate(
            ['code' => 'TP-DE-3006'],
            [
                'name' => 'Gestor III',
                'level' => 'Nivel Profesional',
                'grade' => '03',
                'process' => 'Todos los procesos (Niveles Central y Seccional)',
                'subprocess' => 'Todos los subprocesos asociados al o los proceso(s)',
                'purpose' => 'Desarrollar metodologías, estrategias, técnicas y herramientas de gestión que contribuyan con la toma de decisiones y el direccionamiento hacia el cumplimiento misional, de acuerdo con la normativa vigente, la política institucional y lineamientos técnicos.',
                'is_active' => true,
            ]
        );

        // 2. Super Administrador (Control Central) y Usuario Aspirante Demo
        User::firstOrCreate(
            ['email' => 'admin@dian.gov.co'],
            [
                'name' => 'Control Central DIAN 2676',
                'password' => Hash::make('admin12345'),
                'job_profile_id' => $gestor1->id,
                'target_cargo' => 'Gestor I',
                'role' => 'superadmin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'aspirante@dian.gov.co'],
            [
                'name' => 'Aspirante DIAN 2026',
                'password' => Hash::make('password123'),
                'job_profile_id' => $analista3->id,
                'target_cargo' => 'Analista III',
                'role' => 'aspirante',
            ]
        );

        $this->command->info(" Super Administrador (admin@dian.gov.co / admin12345) y Aspirante demo verificados");

        // 3. Importación de Ejes Temáticos desde JSON (para despliegues en AWS / Docker)
        $axesFile = database_path('seeders/data/thematic_axes.json');
        if (File::exists($axesFile)) {
            $axesData = json_decode(File::get($axesFile), true);
            foreach ($axesData as $ax) {
                ThematicAxis::firstOrCreate(
                    [
                        'name' => $ax['name'],
                        'job_profile_id' => $ax['job_profile_id'] ?? $analista3->id,
                    ],
                    [
                        'description' => $ax['description'] ?? null,
                        'category' => $ax['category'] ?? 'FUNCIONAL',
                        'weight_pct' => $ax['weight_pct'] ?? $ax['weight_percent'] ?? 0,
                    ]
                );
            }
            $this->command->info(" Ejes temáticos sincronizados: " . count($axesData));
        }

        // 4. Importación de Preguntas desde JSON (para despliegues en AWS / Docker)
        $questionsFile = database_path('seeders/data/questions.json');
        if (File::exists($questionsFile)) {
            $questionsData = json_decode(File::get($questionsFile), true);
            $importedCount = 0;
            $validAxisIds = ThematicAxis::pluck('id')->flip()->toArray();
            $validJobIds = JobProfile::pluck('id')->flip()->toArray();

            foreach ($questionsData as $q) {
                if (str_contains($q['stem'], 'Pregunta de prueba para test')) {
                    continue;
                }

                $axisId = (!empty($q['thematic_axis_id']) && isset($validAxisIds[$q['thematic_axis_id']])) ? $q['thematic_axis_id'] : null;
                $jobId = (!empty($q['job_profile_id']) && isset($validJobIds[$q['job_profile_id']])) ? $q['job_profile_id'] : null;

                $exists = Question::where('stem', $q['stem'])
                    ->where(function ($query) use ($jobId, $axisId) {
                        if ($jobId) {
                            $query->where('job_profile_id', $jobId);
                        } else {
                            $query->whereNull('job_profile_id');
                        }
                        if ($axisId) {
                            $query->where('thematic_axis_id', $axisId);
                        } else {
                            $query->whereNull('thematic_axis_id');
                        }
                    })
                    ->exists();

                if (!$exists) {
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

                    Question::create([
                        'job_profile_id' => $jobId,
                        'thematic_axis_id' => $axisId,
                        'case_text' => $q['case_text'] ?? null,
                        'stem' => $q['stem'],
                        'options' => $options,
                        'correct_option' => $q['correct_option'],
                        'explanation_summary' => $q['explanation_summary'] ?? ($q['rationale'] ?? null),
                        'legal_basis' => $q['legal_basis'] ?? null,
                        'question_type' => $q['question_type'] ?? 'situacional',
                        'difficulty' => $q['difficulty'] ?? 'Media',
                        'is_active' => $q['is_active'] ?? true,
                        'is_shared' => $q['is_shared'] ?? true,
                        'scope_level' => $q['scope_level'] ?? 'transversal',
                    ]);
                    $importedCount++;
                }
            }
            $this->command->info(" Preguntas procesadas. Nuevas insertadas: {$importedCount} (Total en base: " . Question::count() . ")");
        }
    }
}
