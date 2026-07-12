<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources\GuruResource\Pages;

use App\Filament\Clusters\DataGuruSiswa\Resources\GuruResource;
use App\Filament\Clusters\DataGuruSiswa\Resources\GuruResource\Imports\GuruImporter;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGurus extends ManageRecords
{
    protected static string $resource = GuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(GuruImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
