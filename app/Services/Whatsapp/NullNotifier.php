<?php

namespace App\Services\Whatsapp;

/**
 * No-op notifier used when WA_NOTIFICATION is off or misconfigured — mirrors
 * Scan::sendNotification()'s silent early-return in the CI4 app.
 */
class NullNotifier implements WhatsappNotifier
{
    public function sendMessage(string $destination, string $message): void
    {
        // Intentionally does nothing.
    }
}
