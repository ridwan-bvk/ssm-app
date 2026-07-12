<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Log;

/**
 * Mirrors Scan::sendNotification() from the CI4 app: WA_NOTIFICATION env
 * flag gates everything, WHATSAPP_PROVIDER selects the implementation
 * (only 'Fonnte' is wired up, matching the old app — the Whatsapp interface
 * was scaffolded for multi-provider support there but no second provider
 * was ever added), and failures never bubble up as exceptions (a scan must
 * never fail because a notification failed).
 */
class WhatsappNotificationManager
{
    public function notify(string $destination, string $message): void
    {
        if (empty($destination)) {
            return;
        }

        try {
            $this->resolveNotifier()->sendMessage($destination, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification error: '.$e->getMessage());
        }
    }

    private function resolveNotifier(): WhatsappNotifier
    {
        if (! filter_var(env('WA_NOTIFICATION', false), FILTER_VALIDATE_BOOLEAN)) {
            return new NullNotifier;
        }

        $provider = env('WHATSAPP_PROVIDER');
        $token = env('WHATSAPP_TOKEN');

        if (empty($provider) || empty($token)) {
            return new NullNotifier;
        }

        return match ($provider) {
            'Fonnte' => new FonnteNotifier($token),
            default => new NullNotifier,
        };
    }
}
