<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guru extends Model
{
    protected $table = 'tb_guru';

    protected $primaryKey = 'id_guru';

    protected $fillable = [
        'nuptk',
        'nama_guru',
        'jenis_kelamin',
        'alamat',
        'no_hp',
        'unique_code',
        'rfid_code',
    ];

    public function kelasWali(): HasOne
    {
        return $this->hasOne(Kelas::class, 'id_wali_kelas', 'id_guru');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(PresensiGuru::class, 'id_guru', 'id_guru');
    }

    public function perizinan(): HasMany
    {
        return $this->hasMany(Perizinan::class, 'id_guru', 'id_guru');
    }

    protected static function booted(): void
    {
        static::creating(function (self $guru): void {
            if (empty($guru->unique_code)) {
                $guru->unique_code = sha1($guru->nama_guru.md5($guru->nuptk.$guru->nama_guru.$guru->no_hp))
                    .substr(sha1($guru->nuptk.random_int(0, 100)), 0, 24);
            }
        });
    }
}
