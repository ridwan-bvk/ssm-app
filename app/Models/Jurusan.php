<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurusan extends Model
{
    use SoftDeletes;

    protected $table = 'tb_jurusan';

    protected $fillable = ['jurusan'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'id_jurusan');
    }
}
