<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors app/Libraries/Whatsapp/Fonnte/Fonnte.php from the CI4 app (same
 * endpoint, same `data` = JSON array of {target, message, delay} payload
 * shape), using Laravel's HTTP client instead of raw curl.
 */
class FonnteNotifier implements WhatsappNotifier
{
    public function __construct(private readonly string $token) {}

    public function sendMessage(string $destination, string $message): void
    {
        $response = Http::withHeaders(['Authorization' => $this->token])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'data' => json_encode([[
                    'target' => $destination,
                    'message' => $message,
                    'delay' => 0,
                ]]),
            ]);

        if (! $response->successful() || ($response->json('status') === false)) {
            Log::error('Fonnte WhatsApp notification failed', [
                'destination' => $destination,
                'response' => $response->body(),
            ]);
        }
    }
}
