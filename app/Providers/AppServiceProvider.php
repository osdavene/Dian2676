<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        Mail::extend('brevo', function (array $config = []) {
            $key = $config['key'] ?? config('services.brevo.key') ?? env('BREVO_API_KEY', '');

            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', $key)
            );
        });
    }
}
