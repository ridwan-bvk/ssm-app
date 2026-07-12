<?php

namespace App\Filament\Clusters\KelasJurusan\Resources\JurusanResource\Imports;

use App\Models\Jurusan;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class JurusanImporter extends Importer
{
    protected static ?string $model = Jurusan::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('jurusan')
                ->requiredMapping()
                ->rules(['required', 'max:32']),
        ];
    }

    public function resolveRecord(): ?Jurusan
    {
        if (Jurusan::where('jurusan', $this->data['jurusan'])->exists()) {
            throw new RowImportFailedException("Jurusan {$this->data['jurusan']} sudah terdaftar.");
        }

        return new Jurusan;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data jurusan selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal (duplikat/tidak valid).';
        }

        return $body;
    }
}
