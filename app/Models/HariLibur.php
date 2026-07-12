<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'tb_hari_libur';

    protected $fillable = ['tanggal', 'keterangan'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
