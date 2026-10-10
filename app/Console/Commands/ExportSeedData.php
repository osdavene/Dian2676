<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ThematicAxis;
use App\Models\Question;
use App\Models\JobProfile;

class ExportSeedData extends Command
{
    protected $signature = 'dian:export-seed';
    protected $description = 'Exporta los ejes temáticos y preguntas a JSON para el seeder en la nube / AWS';

    public function handle(): int
    {
        $dir = database_path('seeders/data');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $axes = ThematicAxis::all()->toArray();
        file_put_contents($dir . '/thematic_axes.json', json_encode($axes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $questions = Question::all()->toArray();
        file_put_contents($dir . '/questions.json', json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exportados " . count($axes) . " ejes temáticos y " . count($questions) . " preguntas a " . $dir);
        return Command::SUCCESS;
    }
}
