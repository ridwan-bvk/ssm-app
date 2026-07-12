<?php

namespace App\Filament\Resources\PerizinanResource\Pages;

use App\Filament\Resources\PerizinanResource;
use Filament\Resources\Pages\ListRecords;

class ListPerizinans extends ListRecords
{
    protected static string $resource = PerizinanResource::class;

    protected function getHeaderActions(): array
    {
        // No manual creation here — izin/sakit requests come from the
        // public portal (Phase 2's /izin page), matching Admin\Perizinan
        // from the CI4 app which has no create action either.
        return [];
    }
}
