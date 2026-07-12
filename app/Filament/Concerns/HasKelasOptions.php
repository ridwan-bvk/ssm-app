<?php

namespace App\Filament\Concerns;

use App\Models\Kelas;
use Illuminate\Support\Collection;

trait HasKelasOptions
{
    protected static function kelasOptions(): Collection
    {
        return Kelas::with('jurusan')->get()
            ->mapWithKeys(fn (Kelas $k) => [$k->id_kelas => static::kelasLabel($k)]);
    }

    protected static function kelasLabel(?Kelas $kelas): string
    {
        return "{$kelas?->tingkat} {$kelas?->jurusan?->jurusan} {$kelas?->index_kelas}";
    }
}
