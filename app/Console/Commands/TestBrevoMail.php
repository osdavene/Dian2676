<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestBrevoMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-brevo {email : Correo destinatario para la prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba usando Brevo API (HTTPS Puerto 443) para verificar la conectividad';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->argument('email');
        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $brevoKey = config('services.brevo.key') ?: env('BREVO_API_KEY');

        $this->info('--- Verificando Configuración de Envío ---');
        $this->line("• Driver activo: <comment>{$mailer}</comment>");
        $this->line("• Remitente: <comment>{$fromName} &lt;{$fromAddress}&gt;</comment>");
        $this->line('• API Key de Brevo: '.(! empty($brevoKey) ? '<info>Configurada ('.substr($brevoKey, 0, 10).'...)</info>' : '<error>No configurada (falta en .env)</error>'));
        $this->line("• Destinatario: <comment>{$recipient}</comment>");
        $this->newLine();

        if (empty($brevoKey) && $mailer === 'brevo') {
            $this->error('ERROR: No has configurado BREVO_API_KEY en tu archivo .env.');
            $this->line('Por favor obtén tu clave gratuita en https://app.brevo.com/settings/keys/api y agrégala a tu .env:');
            $this->line('BREVO_API_KEY=xkeysib-...');

            return Command::FAILURE;
        }

        $this->info("Enviando correo de prueba a {$recipient} mediante Brevo HTTPS API...");

        try {
            Mail::html(
                '<div style="font-family: Arial, sans-serif; padding: 20px; background-color: #0f172a; color: #ffffff; border-radius: 10px;">'.
                '<h2 style="color: #60a5fa;">Conexión con Brevo Exitosa</h2>'.
                '<p>Este es un correo de prueba enviado desde el <strong>Simulador DIAN 2676</strong>.</p>'.
                '<p>El envío se realizó mediante la <strong>API REST HTTPS sobre el puerto 443</strong>, lo que garantiza que funciona en AWS EC2 sin problemas de bloqueo de puertos.</p>'.
                '<p style="color: #94a3b8; font-size: 12px;">Hora del servidor: '.now()->toDateTimeString().'</p>'.
                '</div>',
                function ($message) use ($recipient) {
                    $message->to($recipient)
                        ->subject('Prueba de Conexión — Simulador DIAN 2676');
                }
            );

            $this->newLine();
            $this->info(' ¡Correo enviado exitosamente!');
            $this->line("Revisa la bandeja de entrada (o carpeta de spam/promociones) de <comment>{$recipient}</comment>.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('ERROR AL ENVIAR CORREO: '.$e->getMessage());
            $this->newLine();
            $this->line('Causas frecuentes:');
            $this->line('1. El remitente MAIL_FROM_ADDRESS no está verificado en tu cuenta de Brevo.');
            $this->line('   (Solución: En Brevo ve a "Senders & IP" -> añade tu correo remitente y confírmalo).');
            $this->line('2. La API Key ingresada no es válida o tiene espacios en blanco.');

            return Command::FAILURE;
        }
    }
}
