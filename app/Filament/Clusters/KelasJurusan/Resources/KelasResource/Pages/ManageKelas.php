<?php

namespace App\Filament\Clusters\KelasJurusan\Resources\KelasResource\Pages;

use App\Filament\Clusters\KelasJurusan\Resources\KelasResource;
use App\Filament\Clusters\KelasJurusan\Resources\KelasResource\Imports\KelasImporter;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKelas extends ManageRecords
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(KelasImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
