<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columna question_ids a exam_sessions para fijar las preguntas aleatorias de cada sesión
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_sessions', 'question_ids')) {
                $table->jsonb('question_ids')->nullable()->after('thematic_axis_id');
            }
        });

        // 2. Agregar soporte de preguntas compartidas entre niveles/cargos a questions
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'is_shared')) {
                $table->boolean('is_shared')->default(false)->after('job_profile_id');
            }
            if (!Schema::hasColumn('questions', 'scope_level')) {
                $table->string('scope_level', 50)->default('exclusivo')->after('is_shared');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sessions', 'question_ids')) {
                $table->dropColumn('question_ids');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'is_shared')) {
                $table->dropColumn('is_shared');
            }
            if (Schema::hasColumn('questions', 'scope_level')) {
                $table->dropColumn('scope_level');
            }
        });
    }
};
