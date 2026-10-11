<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\WelcomeMail;
use App\Models\JobProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    protected array $createdEmails = [];

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

        JobProfile::firstOrCreate(
            ['code' => 'AT-FL-2011'],
            [
                'name' => 'Facilitador I',
                'level' => 'Asistencial',
                'grade' => 1,
                'process' => 'Facilitación y Servicio',
                'is_active' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdEmails)) {
            User::whereIn('email', $this->createdEmails)->delete();
            DB::table('password_reset_tokens')->whereIn('email', $this->createdEmails)->delete();
        }
        parent::tearDown();
    }

    public function test_guest_can_view_forgot_password_screen(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Recuperar Contraseña');
        $response->assertSee('Enviar Enlace de Recuperación');
    }

    public function test_user_can_request_password_reset_link_and_receives_mail(): void
    {
        Mail::fake();

        $email = 'candidato.'.uniqid().'@dian.gov.co';
        $this->createdEmails[] = $email;

        $user = User::factory()->create([
            'email' => $email,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $email,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $email,
        ]);

        Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->user->email === $user->email && ! empty($mail->resetUrl);
        });
    }

    public function test_guest_can_view_reset_password_screen_with_token(): void
    {
        $token = Str::random(64);
        $email = 'candidato.'.uniqid().'@dian.gov.co';

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nueva Contraseña');
        $response->assertSee($token);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $email = 'candidato.'.uniqid().'@dian.gov.co';
        $this->createdEmails[] = $email;

        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('ClaveVieja123'),
        ]);

        $token = 'secure-random-token-12345';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClaveSegura2026',
            'password_confirmation' => 'NuevaClaveSegura2026',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClaveSegura2026', $user->password));

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $email = 'candidato.'.uniqid().'@dian.gov.co';
        $this->createdEmails[] = $email;

        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('ClaveVieja123'),
        ]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make('token-correcto'),
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.update'), [
            'token' => 'token-incorrecto',
            'email' => $user->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_register_creates_user_and_sends_welcome_mail(): void
    {
        Mail::fake();

        $email = 'aspirante.'.uniqid().'@dian.gov.co';
        $this->createdEmails[] = $email;

        $job = JobProfile::first();

        $response = $this->post(route('register.post'), [
            'name' => 'Aspirante DIAN Prueba',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'job_profile_id' => $job->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'target_cargo' => $job->name,
        ]);

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($email) {
            return $mail->user->email === $email;
        });
    }

    public function test_google_redirect_handles_missing_credentials_gracefully(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }
}
