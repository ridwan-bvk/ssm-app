<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'tb_siswa';

    protected $primaryKey = 'id_siswa';

    protected $fillable = [
        'nis',
        'nama_siswa',
        'id_kelas',
        'jenis_kelamin',
        'no_hp',
        'poin_pelanggaran',
        'unique_code',
        'rfid_code',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(PresensiSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function perizinan(): HasMany
    {
        return $this->hasMany(Perizinan::class, 'id_siswa', 'id_siswa');
    }

    protected static function booted(): void
    {
        static::creating(function (self $siswa): void {
            if (empty($siswa->unique_code)) {
                $siswa->unique_code = str_replace('.', '-', uniqid('', true)).'-'.random_int(10000000, 99999999);
            }
        });
    }
}
