<?php

namespace App\Filament\Clusters\KelasJurusan\Resources\JurusanResource\Pages;

use App\Filament\Clusters\KelasJurusan\Resources\JurusanResource;
use App\Filament\Clusters\KelasJurusan\Resources\JurusanResource\Imports\JurusanImporter;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJurusans extends ManageRecords
{
    protected static string $resource = JurusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(JurusanImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
