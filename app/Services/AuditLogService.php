<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Mirrors app/Models/AuditLogModel.php's log() method from the CI4 app.
 * There is no automatic model-observer hook in the old app either — every
 * call site logs explicitly, and this port keeps that same explicit-call
 * pattern rather than introducing implicit Eloquent observers.
 */
class AuditLogService
{
    public static function log(string $aksi, ?string $tabel = null, ?int $idRecord = null, ?array $oldData = null, ?array $newData = null): void
    {
        AuditLog::create([
            'id_user' => Auth::id(),
            'aksi' => $aksi,
            'tabel' => $tabel,
            'id_record' => $idRecord,
            'data_lama' => $oldData ? json_encode($oldData) : null,
            'data_baru' => $newData ? json_encode($newData) : null,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
