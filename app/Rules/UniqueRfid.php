<?php

namespace App\Rules;

use App\Models\Guru;
use App\Models\Siswa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mirrors app/Validation/RFIDRules.php from the CI4 app: an RFID code must
 * be unique across both tb_siswa and tb_guru, not just within one table,
 * since a single physical card cannot belong to both a student and a
 * teacher.
 */
class UniqueRfid implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $excludeId = null,
        private readonly ?string $type = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $siswaQuery = Siswa::where('rfid_code', $value);
        if ($this->type === 'siswa' && $this->excludeId) {
            $siswaQuery->where('id_siswa', '!=', $this->excludeId);
        }
        if ($siswaQuery->exists()) {
            $fail('Kode RFID ini sudah digunakan oleh Siswa.');

            return;
        }

        $guruQuery = Guru::where('rfid_code', $value);
        if ($this->type === 'guru' && $this->excludeId) {
            $guruQuery->where('id_guru', '!=', $this->excludeId);
        }
        if ($guruQuery->exists()) {
            $fail('Kode RFID ini sudah digunakan oleh Guru.');
        }
    }
}
