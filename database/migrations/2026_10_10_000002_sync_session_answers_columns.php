<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_sessions')) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('exam_sessions', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('session_answers')) {
            Schema::table('session_answers', function (Blueprint $table) {
                if (!Schema::hasColumn('session_answers', 'exam_session_id')) {
                    $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('session_answers', 'is_omitted')) {
                    $table->boolean('is_omitted')->default(false);
                }
                if (!Schema::hasColumn('session_answers', 'updated_at')) {
                    $table->timestamps();
                }
            });

            // Si session_id existe, sincronizar datos existentes a exam_session_id
            if (Schema::hasColumn('session_answers', 'session_id')) {
                DB::statement('UPDATE session_answers SET exam_session_id = session_id WHERE exam_session_id IS NULL AND session_id IS NOT NULL');
            }
        }
    }

    public function down(): void
    {
    }
};
