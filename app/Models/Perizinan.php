<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perizinan extends Model
{
    protected $table = 'tb_perizinan';

    protected $primaryKey = 'id_perizinan';

    protected $fillable = [
        'id_siswa',
        'id_guru',
        'tanggal_mulai',
        'tanggal_selesai',
        'tipe_izin',
        'alasan',
        'bukti',
        'status',
        'id_petugas',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_petugas', 'id');
    }
}
