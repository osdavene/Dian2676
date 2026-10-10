<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Perfiles de Cargo (Job Profiles)
        if (!Schema::hasTable('job_profiles')) {
            Schema::create('job_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->string('level', 100);
                $table->string('grade', 50);
                $table->string('process', 255)->nullable();
                $table->string('subprocess', 255)->nullable();
                $table->text('purpose')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabla de Ejes Temáticos (Thematic Axes)
        if (!Schema::hasTable('thematic_axes')) {
            Schema::create('thematic_axes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_profile_id')->constrained('job_profiles')->cascadeOnDelete();
                $table->string('category', 50);
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->decimal('weight_pct', 5, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 3. Tabla de Preguntas (Questions)
        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_profile_id')->nullable()->constrained('job_profiles')->cascadeOnDelete();
                $table->foreignId('thematic_axis_id')->nullable()->constrained('thematic_axes')->nullOnDelete();
                $table->string('question_type', 50)->default('situacional');
                $table->string('difficulty', 20)->default('medio');
                $table->text('case_text')->nullable();
                $table->text('stem');
                $table->jsonb('options');
                $table->string('correct_option', 10)->nullable();
                $table->text('legal_basis')->nullable();
                $table->text('explanation_summary')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Agregar columnas de perfil a users si no existen
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'job_profile_id')) {
                $table->foreignId('job_profile_id')->nullable()->constrained('job_profiles')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'target_cargo')) {
                $table->string('target_cargo')->default('Analista III');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('aspirante');
            }
        });

        // 5. Tabla de Sesiones de Examen (Exam Sessions)
        if (!Schema::hasTable('exam_sessions')) {
            Schema::create('exam_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('job_profile_id')->nullable()->constrained('job_profiles')->nullOnDelete();
                $table->string('mode', 50)->default('simulacro_oficial');
                $table->foreignId('thematic_axis_id')->nullable()->constrained('thematic_axes')->nullOnDelete();
                $table->integer('total_questions')->default(0);
                $table->integer('correct_answers')->default(0);
                $table->integer('incorrect_answers')->default(0);
                $table->integer('omitted_answers')->default(0);
                $table->decimal('score', 5, 2)->default(0.00);
                $table->boolean('is_passed')->default(false);
                $table->integer('time_spent_seconds')->default(0);
                $table->integer('time_limit_seconds')->default(18000);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        } else {
            // Asegurar columna user_id en exam_sessions
            Schema::table('exam_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('exam_sessions', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        // 6. Tabla de Respuestas por Sesión (Session Answers)
        if (!Schema::hasTable('session_answers')) {
            Schema::create('session_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
                $table->string('selected_option', 10)->nullable();
                $table->boolean('is_correct')->nullable();
                $table->boolean('is_omitted')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_answers');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('thematic_axes');
        Schema::dropIfExists('job_profiles');
    }
};
