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

        // WebPush client binding
        $this->app->singleton(\Minishlink\WebPush\WebPush::class, function () {
            $config = config('webpush');
            $auth = [];
            $publicKey = $config['vapid']['public_key'] ?? null;
            $privateKey = $config['vapid']['private_key'] ?? null;

            if (!empty($publicKey) && !empty($privateKey)) {
                $auth['VAPID'] = [
                    'subject' => $config['vapid']['subject'] ?? url('/'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ];
            }

            return (new \Minishlink\WebPush\WebPush(
                $auth,
                [],
                new \GuzzleHttp\Client(['timeout' => 30, ...($config['client_options'] ?? [])]),
                new \GuzzleHttp\Psr7\HttpFactory(),
                new \GuzzleHttp\Psr7\HttpFactory()
            ))
            ->setReuseVAPIDHeaders(true)
            ->setAutomaticPadding($config['automatic_padding'] ?? true);
        });
    }

    public function boot(): void
    {
        if (str_contains(request()->url(), 'trycloudflare.com') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
