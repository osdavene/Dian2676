<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateQuestionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dian:deduplicate-questions {--dry-run : Solo analizar sin modificar la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unifica preguntas idénticas (mismo caso y enunciado) marcando la principal como transversal y desactivando clones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $this->info($isDryRun ? '--- MODO SIMULACIÓN (DRY-RUN) ---' : 'Iniciando unificación y deduplicación de preguntas...');

        $allActive = Question::where('is_active', true)->orderBy('id', 'asc')->get();
        $groups = [];

        foreach ($allActive as $q) {
            $cleanCase = trim((string) $q->case_text);
            $cleanStem = trim((string) $q->stem);
            $key = md5($cleanCase.'|||'.$cleanStem);
            $groups[$key][] = $q;
        }

        $groupsWithDuplicates = 0;
        $totalDeactivated = 0;
        $totalPreserved = 0;

        DB::beginTransaction();
        try {
            foreach ($groups as $key => $items) {
                if (count($items) > 1) {
                    $groupsWithDuplicates++;

                    // Conservar la primera pregunta (con ID menor)
                    $primary = $items[0];
                    $duplicates = array_slice($items, 1);

                    if (! $isDryRun) {
                        // Asegurar que la principal sea transversal para que ningún cargo la pierda
                        $primary->update([
                            'is_shared' => true,
                            'scope_level' => 'transversal',
                            'is_active' => true,
                        ]);

                        // Desactivar las copias redundantes
                        foreach ($duplicates as $dup) {
                            $dup->update([
                                'is_active' => false,
                            ]);
                            $totalDeactivated++;
                        }
                    } else {
                        $totalDeactivated += count($duplicates);
                    }

                    $totalPreserved++;
                }
            }

            if (! $isDryRun) {
                DB::commit();

                // Sincronizar questions.json en database/seeders/data
                $path = database_path('seeders/data/questions.json');
                if (file_exists($path)) {
                    $allQuestions = Question::all()->toArray();
                    file_put_contents($path, json_encode($allQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $this->info("✓ Archivo {$path} sincronizado exitosamente.");
                }
            } else {
                DB::rollBack();
            }

            $this->info("✓ Grupos de preguntas duplicadas procesados: {$groupsWithDuplicates}");
            $this->info("✓ Preguntas principales conservadas como transversales: {$totalPreserved}");
            $this->info("✓ Copias redundantes desactivadas: {$totalDeactivated}");
            $this->info('✓ Total preguntas activas resultantes en el banco: '.(Question::where('is_active', true)->count()));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error durante la deduplicación: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
