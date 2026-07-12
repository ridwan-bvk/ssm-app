<?php

namespace App\Services\Whatsapp;

interface WhatsappNotifier
{
    public function sendMessage(string $destination, string $message): void;
}
