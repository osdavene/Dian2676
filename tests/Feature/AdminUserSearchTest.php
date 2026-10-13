<?php

namespace Tests\Feature;

use App\Models\JobProfile;
use App\Models\User;
use Tests\TestCase;

class AdminUserSearchTest extends TestCase
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

    public function test_admin_can_search_user_by_email_and_filter_by_job_profile(): void
    {
        $admin = User::where('role', 'superadmin')->first() ?? User::factory()->create(['role' => 'superadmin']);
        $gestor1 = JobProfile::where('name', 'Gestor I')->first() ?? JobProfile::first();

        $targetUser = User::firstOrCreate(
            ['email' => 'arlethrolong@gmail.com'],
            [
                'name' => 'Arleth Rolong',
                'password' => bcrypt('password123'),
                'job_profile_id' => $gestor1->id,
                'role' => 'aspirante',
            ]
        );

        $response = $this->actingAs($admin)->get('/admin/usuarios?search=arlethrolong%40gmail.com&job_id='.$gestor1->id.'&role=');

        $response->assertStatus(200);
        $response->assertSee('Arleth Rolong');
        $response->assertSee('arlethrolong@gmail.com');
    }

    public function test_admin_can_search_questions_without_driver_syntax_error(): void
    {
        $admin = User::where('role', 'superadmin')->first() ?? User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($admin)->get('/admin/preguntas?search=aduanero');

        $response->assertStatus(200);
    }

    public function test_aspirante_can_search_in_question_bank(): void
    {
        $user = User::factory()->create(['role' => 'aspirante']);

        $response = $this->actingAs($user)->get('/banco-preguntas?search=tributario');

        $response->assertStatus(200);
    }
}
