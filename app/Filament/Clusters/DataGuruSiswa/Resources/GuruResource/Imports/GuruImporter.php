<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources\GuruResource\Imports;

use App\Models\Guru;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Mirrors GuruModel::importCSVItem() from the CI4 app.
 */
class GuruImporter extends Importer
{
    protected static ?string $model = Guru::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nuptk')
                ->requiredMapping()
                ->rules(['required', 'max:24']),
            ImportColumn::make('nama_guru')
                ->requiredMapping()
                ->rules(['required', 'min:3', 'max:255']),
            ImportColumn::make('alamat')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('jenis_kelamin')
                ->requiredMapping()
                ->castStateUsing(function (?string $state): string {
                    $jk = strtolower(trim((string) $state));

                    return match (true) {
                        in_array($jk, ['l', 'laki-laki', 'laki laki', 'laki']) => 'Laki-laki',
                        in_array($jk, ['p', 'perempuan', 'wanita']) => 'Perempuan',
                        default => throw new RowImportFailedException("Jenis kelamin tidak dikenal: {$state}"),
                    };
                })
                ->rules(['required']),
            ImportColumn::make('no_hp')
                ->requiredMapping()
                ->rules(['required', 'numeric']),
        ];
    }

    public function resolveRecord(): ?Guru
    {
        if (Guru::where('nuptk', $this->data['nuptk'])->exists()) {
            throw new RowImportFailedException("NUPTK {$this->data['nuptk']} sudah terdaftar.");
        }

        return new Guru;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data guru selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal (duplikat/tidak valid).';
        }

        return $body;
    }
}
