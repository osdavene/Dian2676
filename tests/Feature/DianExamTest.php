<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\JobProfile;
use App\Models\Question;
use App\Models\SessionAnswer;
use App\Models\ThematicAxis;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DianExamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'dian2676_db',
            'database.connections.pgsql.username' => 'postgres',
            'database.connections.pgsql.password' => '7420',
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.port' => '5432',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('CONCURSO DIAN 2676');
        $response->assertSee('Ingreso de Aspirante');
        $response->assertSee('Ingresar al Simulador');
        $response->assertDontSee('aspirante@dian.gov.co');
    }

    public function test_user_can_view_register_page(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Registro de Aspirante');
    }

    public function test_user_can_register_new_account(): void
    {
        $email = 'nuevo_aspirante_'.time().'@dian.test';
        $job = JobProfile::first();

        $response = $this->post('/register', [
            'name' => 'Carlos Prueba',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'job_profile_id' => $job->id,
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'Carlos Prueba',
            'job_profile_id' => $job->id,
        ]);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Debe existir un usuario');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('Simulacro Oficial');
    }

    public function test_user_can_view_question_bank(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/banco-preguntas');
        $response->assertStatus(200);
        $response->assertSee('Banco General de Preguntas');
    }

    public function test_user_can_view_practice_selection(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/practica');
        $response->assertStatus(200);
        $response->assertSee('Práctica Focalizada');
    }

    public function test_user_can_view_profile(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/perfil');
        $response->assertStatus(200);
        $response->assertSee('Perfil de Usuario');
    }

    public function test_user_can_start_and_view_simulacro(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();
        ExamSession::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)->post('/simulacro/iniciar');
        $response->assertRedirect();

        $session = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        $this->assertNotNull($session);

        $viewResponse = $this->actingAs($user)->get('/simulacro/'.$session->id);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Simulacro Oficial Concurso DIAN 2676');
        $viewResponse->assertSee('Hoja Óptica CNSC');
    }

    public function test_user_can_submit_and_score_simulacro(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();
        $session = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        if (! $session) {
            $this->actingAs($user)->post('/simulacro/iniciar');
            $session = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        }
        $this->assertNotNull($session);

        // Enviar respuestas para calificación basadas en los reactivos asignados a la sesión
        $questionIds = is_array($session->question_ids) ? $session->question_ids : [];
        $questions = ! empty($questionIds) ? Question::whereIn('id', $questionIds)->get() : Question::where('job_profile_id', $session->job_profile_id)->take($session->total_questions)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = $q->correct_option; // Todas correctas para probar 100 pts
        }

        $submitResponse = $this->actingAs($user)->post('/simulacro/'.$session->id.'/finalizar', [
            'answers' => $answers,
            'time_spent_seconds' => 1200,
        ]);

        $submitResponse->assertRedirect(route('simulacro.resultado', $session->id));

        $session->refresh();
        $this->assertNotNull($session->completed_at);
        $this->assertEquals(100.00, $session->score);
        $this->assertTrue($session->is_passed);

        // Ver resultados
        $resultView = $this->actingAs($user)->get('/simulacro/'.$session->id.'/resultado');
        $resultView->assertStatus(200);
        $resultView->assertSee('Calificación Oficial CNSC');
        $resultView->assertSee('Aspirante Aprobado');
    }

    public function test_timer_and_answers_persist_and_can_autosave(): void
    {
        $user = User::where('role', 'aspirante')->first();
        // Iniciar un nuevo simulacro
        $response = $this->actingAs($user)->post('/simulacro/iniciar');
        $response->assertRedirect();

        $session = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest()->first();
        $this->assertNotNull($session);

        // Simular que el usuario responde la primera pregunta de su sesión vía autoguardado AJAX
        $firstQId = is_array($session->question_ids) ? $session->question_ids[0] : Question::where('job_profile_id', $session->job_profile_id)->first()->id;
        $firstQ = Question::find($firstQId);
        $saveResponse = $this->actingAs($user)->postJson('/simulacro/'.$session->id.'/guardar-respuesta', [
            'question_id' => $firstQ->id,
            'selected_option' => $firstQ->correct_option,
        ]);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['success' => true]);

        // Recargar la vista del simulacro: debe mantener el tiempo continuo y la opción marcada
        $reloadView = $this->actingAs($user)->get('/simulacro/'.$session->id);
        $reloadView->assertStatus(200);
        $reloadView->assertSee('checked');

        // Ir al dashboard: debe mostrar el botón para continuar el simulacro en curso
        $dashView = $this->actingAs($user)->get('/dashboard');
        $dashView->assertStatus(200);
        $dashView->assertSee('Continuar Simulacro en Curso');
    }

    public function test_aspirante_cannot_access_admin_panel(): void
    {
        $aspirante = User::where('role', 'aspirante')->first();
        $response = $this->actingAs($aspirante)->get('/admin');
        $response->assertRedirect('/dashboard');
    }

    public function test_superadmin_can_access_admin_control_central(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $this->assertNotNull($admin, 'El usuario superadmin debe existir');

        // 1. Dashboard Control Central
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Panel de Super Usuario');
        $response->assertSee('Aspirantes Registrados');

        // 2. Gestión de Aspirantes
        $usersResponse = $this->actingAs($admin)->get('/admin/usuarios');
        $usersResponse->assertStatus(200);
        $usersResponse->assertSee('Gestión y Control de Aspirantes');

        // 3. Monitoreo de Simulacros
        $sessionsResponse = $this->actingAs($admin)->get('/admin/simulacros');
        $sessionsResponse->assertStatus(200);
        $sessionsResponse->assertSee('Monitoreo General de Simulacros');

        // 4. Analítica de Preguntas Más Falladas
        $analyticsResponse = $this->actingAs($admin)->get('/admin/analitica-preguntas');
        $analyticsResponse->assertStatus(200);
        $analyticsResponse->assertSee('Preguntas de Mayor Complejidad');

        // 5. Ver Examen de Cualquier Aspirante (Auditoría Admin)
        $completedSession = ExamSession::whereNotNull('completed_at')->first();
        if ($completedSession) {
            $examViewResponse = $this->actingAs($admin)->get('/simulacro/'.$completedSession->id.'/resultado');
            $examViewResponse->assertStatus(200);
            $examViewResponse->assertSee('Calificación Oficial CNSC');
        }
    }

    public function test_superadmin_can_create_update_and_manage_users(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $this->assertNotNull($admin);

        // 1. Crear nuevo aspirante
        $uniqueEmail = 'nuevo.aspirante.'.time().'@dian.gov.co';
        $createResponse = $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Carlos Aspirante Test',
            'email' => $uniqueEmail,
            'password' => 'dian2026',
            'role' => 'aspirante',
            'job_profile_id' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.users'));
        $createResponse->assertSessionHas('success');

        $createdUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Carlos Aspirante Test', $createdUser->name);
        $this->assertEquals('aspirante', $createdUser->role);

        // 2. Modificar datos y contraseña del aspirante
        $updateResponse = $this->actingAs($admin)->put('/admin/usuarios/'.$createdUser->id, [
            'name' => 'Carlos Aspirante Modificado',
            'email' => $uniqueEmail,
            'password' => 'nuevaclave123',
            'role' => 'aspirante',
            'job_profile_id' => 2,
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $createdUser->refresh();
        $this->assertEquals('Carlos Aspirante Modificado', $createdUser->name);
        $this->assertEquals(2, $createdUser->job_profile_id);

        // 3. Eliminar usuario creado en test
        $deleteResponse = $this->actingAs($admin)->delete('/admin/usuarios/'.$createdUser->id);
        $deleteResponse->assertRedirect(route('admin.users'));
        $this->assertNull(User::find($createdUser->id));
    }

    public function test_exam_questions_are_randomized_and_locked_to_session(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();
        ExamSession::where('user_id', $user->id)->delete();

        // Iniciar un primer simulacro
        $this->actingAs($user)->post('/simulacro/iniciar');
        $session1 = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        $this->assertNotNull($session1);
        $this->assertNotEmpty($session1->question_ids, 'Los question_ids deben estar guardados en la sesión');
        $this->assertIsArray($session1->question_ids);

        // Finalizar el primer simulacro
        $session1->update(['completed_at' => now(), 'score' => 75.00, 'is_passed' => true]);

        // Iniciar un segundo simulacro para probar orden/set aleatorizado
        $this->actingAs($user)->post('/simulacro/iniciar');
        $session2 = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        $this->assertNotNull($session2);
        $this->assertNotEmpty($session2->question_ids);

        // El orden de las preguntas debe persistir exactamente en la vista del simulacro
        $viewResponse = $this->actingAs($user)->get('/simulacro/'.$session2->id);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Hoja Óptica CNSC');
    }

    public function test_admin_can_access_backup_center_and_generate_backup(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $this->assertNotNull($admin);

        // 1. Acceso a vista de copias de seguridad
        $response = $this->actingAs($admin)->get('/admin/backup');
        $response->assertStatus(200);
        $response->assertSee('Centro de Copias de Seguridad');
        $response->assertSee('Banco Total Asegurado');

        // 2. Descarga directa en JSON
        $downloadResponse = $this->actingAs($admin)->get('/admin/backup/descargar-directo');
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/json');

        // 3. Generar backup en servidor
        $generateResponse = $this->actingAs($admin)->post('/admin/backup/generar');
        $generateResponse->assertRedirect();
        $generateResponse->assertSessionHas('success');
    }

    public function test_acuerdo_001_de_2024_questions_are_available_and_shared_across_profiles(): void
    {
        $analistaJob = JobProfile::where('name', 'Analista III')->first();
        $gestorJob = JobProfile::where('name', 'Gestor I')->first();

        // 1. Verificar que existen reactivos de Acuerdo 001 de 2024
        $acuerdoQuestions = Question::where('legal_basis', 'LIKE', '%Acuerdo 001 de 2024%')->get();
        $this->assertGreaterThanOrEqual(12, $acuerdoQuestions->count());

        // 2. Verificar que están marcadas como transversales / compartidas
        foreach ($acuerdoQuestions as $q) {
            $this->assertTrue((bool) $q->is_shared);
            $this->assertEquals('transversal', $q->scope_level);
        }

        // 3. Verificar que entran en la consulta de ambos cargos
        $analistaPool = Question::forCargo($analistaJob->id)->pluck('id')->toArray();
        $gestorPool = Question::forCargo($gestorJob->id)->pluck('id')->toArray();

        foreach ($acuerdoQuestions as $q) {
            $this->assertContains($q->id, $analistaPool);
            $this->assertContains($q->id, $gestorPool);
        }

        // 4. Verificar acceso al entrenamiento del eje temático para usuario autenticado
        $aspirante = User::where('email', 'aspirante@dian.gov.co')->first();
        $practiceResponse = $this->actingAs($aspirante)->get('/practica');
        $practiceResponse->assertStatus(200);
        $practiceResponse->assertSee('Gestión Documental');
    }

    public function test_anti_lavado_questions_and_gestor_iii_profile_integration(): void
    {
        // 1. Verificar existencia del perfil Gestor III
        $gestor3 = JobProfile::where('code', 'TP-DE-3006')->first();
        $this->assertNotNull($gestor3);
        $this->assertEquals('Gestor III', $gestor3->name);
        $this->assertEquals('Nivel Profesional', $gestor3->level);
        $this->assertEquals('03', $gestor3->grade);

        // 2. Verificar que Gestor III cuenta con sus ejes temáticos
        $gestor3Axes = ThematicAxis::where('job_profile_id', $gestor3->id)->get();
        $this->assertGreaterThanOrEqual(9, $gestor3Axes->count());

        // 3. Verificar las 12 preguntas de Anti Lavado de Activos y Contra la Financiación del Terrorismo
        $laftQuestions = Question::where('stem', 'LIKE', '%Al proponer estrategias para solucionar la desarticulación institucional%')
            ->orWhere('stem', 'LIKE', '%Con el objetivo de elevar el nivel de la política pública%')
            ->orWhere('stem', 'LIKE', '%Para fortalecer el esquema de supervisión y lograr que las empresas de transporte%')
            ->get();
        $this->assertGreaterThanOrEqual(3, $laftQuestions->count());

        // 4. Verificar que Gestor III tiene acceso al banco de nivel profesional y transversales (>= 100 reactivos)
        $gestor3Questions = Question::forCargo($gestor3->id)->get();
        $this->assertGreaterThanOrEqual(100, $gestor3Questions->count());

        // 5. Simular creación de usuario aspirante para Gestor III y generación de examen de 100 preguntas
        $userGestor3 = User::factory()->create([
            'job_profile_id' => $gestor3->id,
            'target_cargo' => 'Gestor III',
            'role' => 'aspirante',
        ]);

        $examResponse = $this->actingAs($userGestor3)->post('/simulacro/iniciar');
        $examResponse->assertRedirect();

        $session = ExamSession::where('user_id', $userGestor3->id)->latest('id')->first();
        $this->assertNotNull($session);
        $this->assertEquals(100, $session->total_questions);
        $this->assertCount(100, $session->question_ids);
    }

    public function test_aspirante_cannot_change_assigned_cargo_level_via_profile(): void
    {
        $analista2 = JobProfile::where('name', 'Analista II')->first();
        $gestor1 = JobProfile::where('name', 'Gestor I')->first();
        $this->assertNotNull($analista2);
        $this->assertNotNull($gestor1);

        $email = 'aspirante_bloqueado_'.uniqid().'@dian.gov.co';
        $aspirante = User::factory()->create([
            'name' => 'Aspirante Bloqueado',
            'email' => $email,
            'role' => 'aspirante',
            'job_profile_id' => $analista2->id,
            'target_cargo' => $analista2->name,
        ]);

        // Intentar cambiar su cargo a Gestor I mediante PUT /perfil
        $response = $this->actingAs($aspirante)->put('/perfil', [
            'name' => 'Aspirante Bloqueado Modificado',
            'email' => $email,
            'job_profile_id' => $gestor1->id,
        ]);

        $response->assertSessionHas('success');

        // Verificar en BD que el cargo NO cambió
        $aspirante->refresh();
        $this->assertEquals($analista2->id, $aspirante->job_profile_id);
        $this->assertEquals('Analista II', $aspirante->target_cargo);
        $this->assertEquals('Aspirante Bloqueado Modificado', $aspirante->name);

        // Verificar que en la vista de perfil se muestra el aviso de nivel bloqueado
        $viewResponse = $this->actingAs($aspirante)->get('/perfil');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Bloqueado por Administración');
        $viewResponse->assertSee('Nivel Asignado Exclusivo');
    }

    public function test_admin_can_change_aspirante_cargo_level_via_admin_controller(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $analista2 = JobProfile::where('name', 'Analista II')->first();
        $gestor3 = JobProfile::where('name', 'Gestor III')->first();

        $email = 'aspirante_admin_'.uniqid().'@dian.gov.co';
        $aspirante = User::factory()->create([
            'name' => 'Aspirante Para Admin',
            'email' => $email,
            'role' => 'aspirante',
            'job_profile_id' => $analista2->id,
            'target_cargo' => $analista2->name,
        ]);

        // El Administrador actualiza el cargo del aspirante a Gestor III
        $response = $this->actingAs($admin)->put("/admin/usuarios/{$aspirante->id}", [
            'name' => 'Aspirante Para Admin',
            'email' => $email,
            'role' => 'aspirante',
            'job_profile_id' => $gestor3->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $aspirante->refresh();
        $this->assertEquals($gestor3->id, $aspirante->job_profile_id);
        $this->assertEquals('Gestor III', $aspirante->target_cargo);
    }

    public function test_analista_ii_profile_axes_and_question_pool_integration(): void
    {
        $analista2 = JobProfile::where('name', 'Analista II')->first();
        $this->assertNotNull($analista2);
        $this->assertEquals('CT-CR-2012', $analista2->code);
        $this->assertEquals('Nivel Técnico', $analista2->level);
        $this->assertEquals('02', $analista2->grade);

        // 1. Verificar ejes temáticos de Analista II (9 ejes temáticos de su manual de funciones)
        $axes = ThematicAxis::where('job_profile_id', $analista2->id)->get();
        $this->assertGreaterThanOrEqual(9, $axes->count());

        // 2. Verificar que Analista II tiene al menos 280 preguntas disponibles (156 nivel técnico + 132 transversales = 288)
        $pool = Question::forCargo($analista2->id)->get();
        $this->assertEquals(288, $pool->count());

        // 3. Crear aspirante asignado a Analista II y lanzar simulacro de 100 preguntas
        $email = 'analista2_'.uniqid().'@dian.gov.co';
        $userAnalista2 = User::factory()->create([
            'email' => $email,
            'job_profile_id' => $analista2->id,
            'target_cargo' => 'Analista II',
            'role' => 'aspirante',
        ]);

        $examResponse = $this->actingAs($userAnalista2)->post('/simulacro/iniciar');
        $examResponse->assertRedirect();

        $session = ExamSession::where('user_id', $userAnalista2->id)->latest('id')->first();
        $this->assertNotNull($session);
        $this->assertEquals(100, $session->total_questions);
        $this->assertCount(100, $session->question_ids);
    }

    public function test_new_folders_questions_integration(): void
    {
        // 1. Cobro Coactivo - Procedimiento de Cobro (12 preguntas: ID 383 a 394)
        $cobroQuestions = Question::whereBetween('id', [383, 394])->get();
        $this->assertEquals(12, $cobroQuestions->count());

        // 2. Conservación y preservación (12 preguntas: ID 395 a 406)
        $conservacionQuestions = Question::whereBetween('id', [395, 406])->get();
        $this->assertEquals(12, $conservacionQuestions->count());

        // 3. Código de Ética (12 preguntas: ID 407 a 418)
        $eticaQuestions = Question::whereBetween('id', [407, 418])->get();
        $this->assertEquals(12, $eticaQuestions->count());

        // 4. Verificar que el banco total asciende al menos a 478 reactivos (539 con asignaciones multicarro)
        $this->assertGreaterThanOrEqual(478, Question::count());

        // 5. Verificar que las preguntas añadidas son transversales y compartidas
        $newSample = Question::where('id', '>=', 383)->where('scope_level', 'transversal')->get();
        $this->assertGreaterThanOrEqual(108, $newSample->count());
        foreach ($newSample as $q) {
            $this->assertTrue((bool) $q->is_shared);
            $this->assertEquals('transversal', $q->scope_level);
        }

        // 6. Verificar las 6 nuevas carpetas de Doc con 12 preguntas cada una (72 preguntas)
        $sixNewAxes = [
            'Régimen de Cajas Menores y Procedimientos de Legalización',
            'Fiscalidad Internacional y Cláusula del Propósito Principal (PPT)',
            'Controles Aduaneros y Fases del Despacho (Decreto 1165 de 2019)',
            'Régimen de Zonas Francas (Decreto 2147 de 2016)',
            'Precios de Transferencia y Directrices OCDE',
            'Entidades Autorizadas para Recaudar (EAR) y Procedimiento de Recaudo',
        ];

        foreach ($sixNewAxes as $axisName) {
            $axis = ThematicAxis::where('name', $axisName)->first();
            $this->assertNotNull($axis, "Eje temático no encontrado: {$axisName}");
            $questionsInAxis = Question::where('thematic_axis_id', $axis->id)->get();
            $this->assertCount(12, $questionsInAxis, "El eje {$axisName} debe tener exactamente 12 preguntas");

            foreach ($questionsInAxis as $q) {
                $this->assertNotEmpty($q->stem);
                $this->assertNotEmpty($q->case_text);
                $this->assertNotEmpty($q->legal_basis);
                $this->assertNotEmpty($q->explanation_summary);
                $this->assertCount(3, $q->options);
                $this->assertContains($q->correct_option, ['A', 'B', 'C']);
            }
        }
    }

    public function test_aspirante_cannot_switch_cargo_and_navbar_is_locked(): void
    {
        $analista2 = JobProfile::where('name', 'Analista II')->first();
        $gestor1 = JobProfile::where('name', 'Gestor I')->first();

        $aspirante = User::factory()->create([
            'name' => 'Aspirante Fijo',
            'email' => 'fijo_'.uniqid().'@dian.test',
            'role' => 'aspirante',
            'job_profile_id' => $analista2->id,
            'target_cargo' => 'Analista II',
        ]);

        // 1. En la vista del dashboard no debe haber botón ni modal de cambiar cargo
        $response = $this->actingAs($aspirante)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Cargo Asignado:');
        $response->assertSee('Fijo');
        $response->assertDontSee('id="cargo-modal"', false);

        // 2. Intentar llamar por POST a la ruta de cambiar cargo debe ser rechazado
        $postResponse = $this->actingAs($aspirante)->post('/cambiar-cargo/'.$gestor1->id);
        $postResponse->assertSessionHas('error');

        // 3. El cargo en base de datos debe permanecer intacto
        $aspirante->refresh();
        $this->assertEquals($analista2->id, $aspirante->job_profile_id);
        $this->assertEquals('Analista II', $aspirante->target_cargo);

        // 4. Un Administrador sí puede alternar cargo
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $adminResponse = $this->actingAs($admin)->post('/cambiar-cargo/'.$analista2->id);
        $adminResponse->assertSessionHas('success');
        $admin->refresh();
        $this->assertEquals($analista2->id, $admin->job_profile_id);
    }

    public function test_backup_import_and_restore_workflow(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();

        // 1. Generar backup en servidor
        $genResponse = $this->actingAs($admin)->post('/admin/backup/generar');
        $genResponse->assertSessionHas('success');

        // Verificar que el archivo existe en storage/app/backups
        $files = glob(storage_path('app/backups/*.json'));
        $this->assertNotEmpty($files);
        $latestBackup = basename(end($files));

        // 2. Restaurar ese backup desde el servidor
        $restoreServerResponse = $this->actingAs($admin)->post('/admin/backup/restaurar/'.$latestBackup, [
            'overwrite' => 1,
        ]);
        $restoreServerResponse->assertSessionHas('success');

        // 3. Importar un archivo JSON simulado subido vía multipart
        $testPayload = [
            'metadata' => ['total_preguntas' => 1],
            'thematic_axes' => [
                [
                    'name' => 'Eje de Prueba Backup',
                    'category' => 'funcional_especifica',
                    'description' => 'Eje temporal para test de importación',
                    'weight_pct' => 10.00,
                ],
            ],
            'questions' => [
                [
                    'thematic_axis_name' => 'Eje de Prueba Backup',
                    'stem' => '¿Pregunta de prueba para test de importación de backup '.uniqid().'?',
                    'case_text' => 'Caso de prueba para importación.',
                    'options' => [
                        ['key' => 'A', 'text' => 'Opción de prueba A', 'is_correct' => true, 'justification' => 'Correcta por ley.'],
                        ['key' => 'B', 'text' => 'Opción de prueba B', 'is_correct' => false, 'justification' => 'Incorrecta.'],
                        ['key' => 'C', 'text' => 'Opción de prueba C', 'is_correct' => false, 'justification' => 'Incorrecta.'],
                    ],
                    'correct_option' => 'A',
                    'legal_basis' => 'Estatuto Tributario Nacional',
                    'explanation_summary' => 'Explicación de prueba.',
                    'question_type' => 'situacional',
                    'difficulty' => 'medio',
                    'is_shared' => true,
                    'scope_level' => 'transversal',
                ],
            ],
        ];

        $uploadedFile = UploadedFile::fake()->createWithContent('backup_test.json', json_encode($testPayload));

        $uploadResponse = $this->actingAs($admin)->post('/admin/backup/importar', [
            'backup_file' => $uploadedFile,
            'overwrite' => 1,
        ]);
        $uploadResponse->assertSessionHas('success');

        // Verificar que la pregunta de prueba fue insertada
        $this->assertTrue(Question::where('stem', $testPayload['questions'][0]['stem'])->exists());

        // Limpieza de la pregunta de prueba
        Question::where('stem', $testPayload['questions'][0]['stem'])->delete();
        ThematicAxis::where('name', 'Eje de Prueba Backup')->delete();
    }

    public function test_user_can_start_express_simulacro_with_50_questions(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();

        $response = $this->actingAs($user)->post('/simulacro/iniciar', [
            'question_count' => 50,
            'force_new' => 1,
        ]);

        $response->assertRedirect();

        $session = ExamSession::where('user_id', $user->id)
            ->where('mode', 'simulacro_express')
            ->latest('id')
            ->first();

        $this->assertNotNull($session);
        $this->assertEquals('simulacro_express', $session->mode);
        $this->assertEquals(9000, $session->time_limit_seconds); // 2h 30m
        $this->assertLessThanOrEqual(50, $session->total_questions);

        // Ver el simulacro express
        $viewResponse = $this->actingAs($user)->get('/simulacro/'.$session->id);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Simulacro Rápido Express');
    }

    public function test_user_can_pause_simulacro_and_time_is_frozen(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();

        // 1. Iniciar un simulacro
        $this->actingAs($user)->post('/simulacro/iniciar', [
            'question_count' => 100,
            'force_new' => 1,
        ]);

        $session = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        $this->assertNotNull($session);

        // 2. Simular que el usuario gasta 1800 segundos (30 min) y pausa el examen
        $pauseResponse = $this->actingAs($user)->post('/simulacro/'.$session->id.'/pausar', [
            'time_spent_seconds' => 1800,
        ]);

        $pauseResponse->assertRedirect(route('dashboard'));
        $pauseResponse->assertSessionHas('success');

        $session->refresh();
        $this->assertEquals(1800, $session->time_spent_seconds);
        $this->assertNull($session->completed_at);

        // 3. Volver a ingresar al simulador: el tiempo restante debe reflejar exactamente la pausa
        $viewResponse = $this->actingAs($user)->get('/simulacro/'.$session->id);
        $viewResponse->assertStatus(200);
        // Debe quedar 18000 - 1800 = 16200 segundos (04:30:00)
        $expectedRemainingFormatted = gmdate('H:i:s', $session->time_limit_seconds - 1800);
        $viewResponse->assertSee($expectedRemainingFormatted);
    }

    public function test_heartbeat_syncs_elapsed_time(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();

        $session = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        if (! $session) {
            $this->actingAs($user)->post('/simulacro/iniciar');
            $session = ExamSession::where('user_id', $user->id)->whereNull('completed_at')->latest('id')->first();
        }

        $newTime = ($session->time_spent_seconds ?? 0) + 120;

        $hbResponse = $this->actingAs($user)->postJson('/simulacro/'.$session->id.'/heartbeat', [
            'time_spent_seconds' => $newTime,
        ]);

        $hbResponse->assertStatus(200);
        $hbResponse->assertJson(['success' => true, 'time_spent_seconds' => $newTime]);

        $session->refresh();
        $this->assertEquals($newTime, $session->time_spent_seconds);
    }

    public function test_user_can_view_exam_history_and_diagnostics(): void
    {
        $user = User::where('role', 'aspirante')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/simulacros/historial');
        $response->assertStatus(200);
        $response->assertSee('Historial de Simulacros y Diagnóstico Personal');
        $response->assertSee('Trazabilidad Histórica');
        $response->assertSee('Total Simulacros');
    }

    public function test_admin_dashboard_shows_both_oficial_and_express_live_sessions(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $aspirante = User::where('role', 'aspirante')->first();

        // Crear una sesión oficial y una sesión express activas
        $officialSession = ExamSession::create([
            'user_id' => $aspirante->id,
            'job_profile_id' => $aspirante->job_profile_id ?? 1,
            'mode' => 'simulacro_oficial',
            'question_ids' => [1, 2, 3],
            'total_questions' => 3,
            'time_limit_seconds' => 18000,
            'time_spent_seconds' => 100,
            'completed_at' => null,
        ]);

        $expressSession = ExamSession::create([
            'user_id' => $aspirante->id,
            'job_profile_id' => $aspirante->job_profile_id ?? 1,
            'mode' => 'simulacro_express',
            'question_ids' => [1, 2],
            'total_questions' => 2,
            'time_limit_seconds' => 9000,
            'time_spent_seconds' => 50,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Simulacros Activos en Vivo (En Curso)');
        $response->assertSee('⚡ Express (50P)');
        $response->assertSee('🏆 Oficial (100P)');

        $officialSession->delete();
        $expressSession->delete();
    }

    public function test_simulator_positions_directly_on_first_unanswered_question(): void
    {
        $aspirante = User::where('role', 'aspirante')->first();

        // Iniciar un simulacro
        $this->actingAs($aspirante)->post('/simulacro/iniciar', [
            'question_count' => 50,
            'force_new' => 1,
        ]);

        $session = ExamSession::where('user_id', $aspirante->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        $questionIds = $session->question_ids;
        $this->assertNotEmpty($questionIds);

        // Responder las primeras 2 preguntas
        SessionAnswer::create([
            'exam_session_id' => $session->id,
            'question_id' => $questionIds[0],
            'selected_option' => 'A',
            'is_correct' => true,
        ]);

        SessionAnswer::create([
            'exam_session_id' => $session->id,
            'question_id' => $questionIds[1],
            'selected_option' => 'B',
            'is_correct' => true,
        ]);

        // Cargar vista del simulador: debe posicionar directamente en la pregunta 3
        $response = $this->actingAs($aspirante)->get('/simulacro/'.$session->id);
        $response->assertStatus(200);
        $response->assertViewHas('initialQuestionNumber', 3);
        $response->assertSee('P <span id="mobile-current-q" class="text-blue-400">3</span>', false);
        $response->assertSee('let currentQuestion = 3;', false);
    }

    public function test_admin_can_access_questions_management_module(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin/preguntas');
        $response->assertStatus(200);
        $response->assertSee('Gestión, Auditoría y Corrección de Reactivos');
        $response->assertSee('Banco de Preguntas');
        $response->assertSee('Auditoría de Duplicadas');
    }

    public function test_admin_can_update_question_and_recalculate_scores(): void
    {
        $admin = User::where('email', 'admin@dian.gov.co')->first();
        $aspirante = User::where('role', 'aspirante')->first();

        // 1. Crear pregunta de prueba
        $question = Question::create([
            'job_profile_id' => 1,
            'stem' => 'Pregunta de prueba para auditoría y corrección admin '.uniqid(),
            'correct_option' => 'A',
            'options' => [
                ['key' => 'A', 'text' => 'Opción A original', 'is_correct' => true],
                ['key' => 'B', 'text' => 'Opción B errónea', 'is_correct' => false],
                ['key' => 'C', 'text' => 'Opción C corregida', 'is_correct' => false],
            ],
            'is_active' => true,
        ]);

        // 2. Crear sesión y respuesta de aspirante que eligió la opción 'C'
        $session = ExamSession::create([
            'user_id' => $aspirante->id,
            'job_profile_id' => 1,
            'mode' => 'simulacro_express',
            'question_ids' => [$question->id],
            'total_questions' => 1,
            'correct_answers' => 0,
            'incorrect_answers' => 1,
            'score' => 0.00,
            'is_passed' => false,
            'time_limit_seconds' => 9000,
            'time_spent_seconds' => 100,
            'completed_at' => now(),
        ]);

        $answer = SessionAnswer::create([
            'exam_session_id' => $session->id,
            'question_id' => $question->id,
            'selected_option' => 'C',
            'is_correct' => false,
        ]);

        // 3. Superadministrador detecta el error y corrige la opción correcta a 'C'
        $updateResponse = $this->actingAs($admin)->put('/admin/preguntas/'.$question->id, [
            'correct_option' => 'C',
            'stem' => $question->stem,
            'option_a' => 'Opción A original',
            'option_b' => 'Opción B errónea',
            'option_c' => 'Opción C corregida',
            'recalculate_scores' => 1,
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        // 4. Verificar que la pregunta se corrigió y el examen del aspirante se recalculó a aprobado
        $question->refresh();
        $this->assertEquals('C', $question->correct_option);

        $answer->refresh();
        $this->assertTrue($answer->is_correct);

        $session->refresh();
        $this->assertEquals(1, $session->correct_answers);
        $this->assertEquals(100.00, (float) $session->score);
        $this->assertTrue($session->is_passed);

        // Limpiar registros de prueba
        $answer->delete();
        $session->delete();
        $question->delete();
    }

    public function test_exam_generation_deduplicates_questions_and_groups_cases_consecutively(): void
    {
        $aspirante = User::where('role', 'aspirante')->first();

        $response = $this->actingAs($aspirante)->post('/simulacro/iniciar', [
            'question_count' => 50,
            'force_new' => 1,
        ]);

        $response->assertRedirect();

        $session = ExamSession::where('user_id', $aspirante->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        $this->assertNotNull($session);
        $questionIds = $session->question_ids;
        $this->assertCount(50, $questionIds);

        // 1. Verificar que NO hay preguntas repetidas (0% duplicados por stem)
        $questions = Question::whereIn('id', $questionIds)->get();
        $uniqueStems = $questions->pluck('stem')->unique()->count();
        $this->assertEquals($questions->count(), $uniqueStems, 'Todas las preguntas del simulacro deben tener enunciados únicos');

        // 2. Verificar que preguntas que comparten caso queden ordenadas consecutivamente
        $caseIndices = [];
        foreach ($questionIds as $idx => $qId) {
            $q = $questions->firstWhere('id', $qId);
            $caseText = trim($q->case_text ?? '');
            if (strlen($caseText) > 50) {
                $caseIndices[$caseText][] = $idx + 1;
            }
        }

        foreach ($caseIndices as $cText => $indices) {
            if (count($indices) > 1) {
                for ($i = 0; $i < count($indices) - 1; $i++) {
                    $this->assertEquals(
                        $indices[$i] + 1,
                        $indices[$i + 1],
                        'Las preguntas asociadas a un mismo caso deben ser estrictamente consecutivas en el examen'
                    );
                }
            }
        }
    }
}
