<?php

namespace App\Filament\Resources\UserResource\Imports;

use App\Models\Guru;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

/**
 * Mirrors PetugasModel::importCSVItem() from the CI4 app: bulk staff
 * account creation with duplicate email/username checks, id_guru FK
 * validation, and password hashing.
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Username')
                ->requiredMapping()
                ->rules(['required', 'min:6', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email']),
            ImportColumn::make('password')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->rules(['required', 'min:6']),
            ImportColumn::make('role')
                ->requiredMapping()
                ->rules(['required', 'in:superadmin,admin,kepsek,scanner'])
                // Not a real `users` column — roles are a Spatie pivot
                // relation, synced in afterSave() below. Without this, the
                // base Importer directly sets $record->role, which fails
                // at save() since no such column exists.
                ->fillRecordUsing(fn () => null),
            ImportColumn::make('id_guru')
                ->rules(['nullable', 'integer']),
        ];
    }

    public function resolveRecord(): ?User
    {
        if (User::where('email', $this->data['email'])->exists()) {
            throw new RowImportFailedException("Email {$this->data['email']} sudah terdaftar.");
        }

        if (User::where('name', $this->data['name'])->exists()) {
            throw new RowImportFailedException("Username {$this->data['name']} sudah terdaftar.");
        }

        if (filled($this->data['id_guru'] ?? null) && ! Guru::where('id_guru', $this->data['id_guru'])->exists()) {
            throw new RowImportFailedException("Guru dengan ID {$this->data['id_guru']} tidak ditemukan.");
        }

        return new User;
    }

    protected function afterSave(): void
    {
        $roles = [$this->data['role']];

        if (filled($this->data['id_guru'] ?? null)) {
            $roles[] = 'guru';
        }

        $this->record->syncRoles($roles);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data petugas selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal (duplikat/tidak valid).';
        }

        return $body;
    }
}
