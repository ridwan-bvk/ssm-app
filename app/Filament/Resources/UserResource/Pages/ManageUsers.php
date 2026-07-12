<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Imports\UserImporter;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()->importer(UserImporter::class),
            Actions\CreateAction::make()
                ->after(function (User $record, array $data): void {
                    $roles = [$data['role']];
                    if ($record->id_guru) {
                        $roles[] = 'guru';
                    }
                    $record->syncRoles($roles);
                }),
        ];
    }
}
