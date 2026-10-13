<?php

use App\Models\JobProfile;
use App\Models\ThematicAxis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna options_order a exam_sessions para barajado aleatorio y determinista por sesión
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'options_order')) {
                $table->json('options_order')->nullable()->after('question_ids');
            }
        });

        // 2. Crear el empleo Gestor II si no existe
        $gestor2 = JobProfile::firstOrCreate(
            ['code' => 'CT-CR-3007'],
            [
                'name' => 'Gestor II',
                'level' => 'Nivel Profesional',
                'grade' => '02',
                'process' => 'MISIONAL: Cumplimiento de obligaciones tributarias',
                'subprocess' => 'Administración de cartera, Recaudo-Devoluciones',
                'purpose' => 'Desarrollar acciones inherentes al proceso de Cumplimiento de Obligaciones Tributarias, de acuerdo con la normativa vigente, las políticas gubernamentales e institucionales y las directrices de Nivel Central.',
                'is_active' => true,
            ]
        );

        // 3. Ejes temáticos funcionales específicos para Gestor II
        $axes = [
            [
                'name' => 'Cobro Coactivo y Procedimiento Administrativo de Cobro',
                'description' => 'Mandamiento de pago, excepciones, prescripción de la acción de cobro y liquidación de créditos según el Estatuto Tributario.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Medidas Cautelares y Garantías Tributarias',
                'description' => 'Embargos de cuentas bancarias, bienes sujetos a registro, secuestros y constitución de pólizas de garantía.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Devoluciones y Compensaciones de Tributos',
                'description' => 'Trámite de solicitudes de devolución y compensación de saldos a favor en Renta e IVA, devoluciones automáticas e improcedentes.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Cobro Persuasivo y Acuerdos de Pago',
                'description' => 'Campañas de cobro preventivo, facilidades para el pago (Art. 814 E.T.) y normalización de obligaciones tributarias.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Control Extensivo de Obligaciones y Normalización',
                'description' => 'Depuración de estados de cuenta, corrección de inconsistencias en declaraciones y reproceso de saldos.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Procesos Concursales y Reorganización Empresarial',
                'description' => 'Representación de la DIAN en acuerdos de reorganización, liquidación judicial (Ley 1116 de 2006) y acreencias fiscales.',
                'category' => 'funcional_especifica',
                'weight_pct' => 15.00,
            ],
            [
                'name' => 'Entidades Autorizadas para Recaudar (EAR)',
                'description' => 'Auditoría, seguimiento, validación de consignaciones y sanciones a bancos y entidades recaudadoras autorizadas.',
                'category' => 'funcional_especifica',
                'weight_pct' => 10.00,
            ],
        ];

        foreach ($axes as $ax) {
            ThematicAxis::firstOrCreate(
                [
                    'job_profile_id' => $gestor2->id,
                    'name' => $ax['name'],
                ],
                [
                    'description' => $ax['description'],
                    'category' => $ax['category'],
                    'weight_pct' => $ax['weight_pct'],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sessions', 'options_order')) {
                $table->dropColumn('options_order');
            }
        });
    }
};
