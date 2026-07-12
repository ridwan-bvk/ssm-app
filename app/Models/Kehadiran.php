<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kehadiran extends Model
{
    public $timestamps = false;

    protected $table = 'tb_kehadiran';

    protected $primaryKey = 'id_kehadiran';

    protected $fillable = ['kehadiran'];

    public const HADIR = 1;

    public const SAKIT = 2;

    public const IZIN = 3;

    public const TANPA_KETERANGAN = 4;
}
