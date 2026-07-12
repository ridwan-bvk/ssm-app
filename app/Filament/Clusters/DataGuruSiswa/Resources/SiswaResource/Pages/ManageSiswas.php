<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources\SiswaResource\Pages;

use App\Filament\Clusters\DataGuruSiswa\Resources\SiswaResource;
use App\Filament\Clusters\DataGuruSiswa\Resources\SiswaResource\Imports\SiswaImporter;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSiswas extends ManageRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(SiswaImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
