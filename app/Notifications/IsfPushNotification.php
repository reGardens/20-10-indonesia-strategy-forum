<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IsfPushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public array $data = []
    ) {
        $this->icon = $this->icon ?? '/icons/icon-192x192.png';
        $this->badge = $this->badge ?? '/icons/badge-72x72.png';
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $actionUrl = $this->url ?? url('/');
        $payloadData = array_merge([
            'url' => $actionUrl,
            'timestamp' => now()->toIso8601String(),
        ], $this->data);

        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->badge($this->badge)
            ->data($payloadData)
            ->options([
                'TTL' => 86400,
                'urgency' => 'high',
            ]);
    }
}
