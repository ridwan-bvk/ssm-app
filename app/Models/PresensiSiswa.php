<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiSiswa extends Model
{
    public $timestamps = false;

    protected $table = 'tb_presensi_siswa';

    protected $primaryKey = 'id_presensi';

    protected $fillable = [
        'id_siswa',
        'id_kelas',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'id_kehadiran',
        'menit_keterlambatan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function kehadiran(): BelongsTo
    {
        return $this->belongsTo(Kehadiran::class, 'id_kehadiran', 'id_kehadiran');
    }
}
