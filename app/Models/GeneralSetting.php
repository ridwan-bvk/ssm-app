<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    public $timestamps = false;

    protected $table = 'general_settings';

    protected $fillable = [
        'logo',
        'school_name',
        'school_year',
        'jam_masuk_limit',
        'jam_pulang_standard',
        'hari_kerja',
        'copyright',
    ];
}
