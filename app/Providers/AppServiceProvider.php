<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pengirim WhatsApp (Issue #42): Fonnte bila dikonfigurasi, selain itu driver log.
        $this->app->bind(\App\Services\WhatsApp\WhatsAppSender::class, function () {
            $token = (string) config('services.fonnte.token');

            if (config('services.whatsapp.driver') === 'fonnte' && $token !== '') {
                return new \App\Services\WhatsApp\FonnteSender($token, (string) config('services.fonnte.endpoint'));
            }

            return new \App\Services\WhatsApp\LogSender();
        });
    }

    public function boot(): void
    {
        if (str_contains(request()->url(), 'trycloudflare.com') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
