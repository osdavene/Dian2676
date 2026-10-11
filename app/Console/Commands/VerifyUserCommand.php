<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verify {email : Correo electrónico del usuario a verificar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica manualmente el correo electrónico de un usuario para acceso inmediato';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No se encontró ningún usuario con el correo: {$email}");

            return Command::FAILURE;
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $this->info("¡Usuario {$user->name} ({$email}) verificado con éxito!");
        $this->line('El usuario ya puede recargar la página en su navegador para acceder inmediatamente a los simulacros.');

        return Command::SUCCESS;
    }
}
