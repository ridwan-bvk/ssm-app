<?php

namespace App\Filament\Clusters\KelasJurusan\Resources\KelasResource\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Mirrors KelasModel::importCSVItem() from the CI4 app: jurusan is matched
 * by exact name (must already exist — import jurusan first), duplicate
 * (tingkat, id_jurusan, index_kelas) is rejected.
 */
class KelasImporter extends Importer
{
    protected static ?string $model = Kelas::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('tingkat')
                ->requiredMapping()
                ->rules(['required', 'max:10']),
            ImportColumn::make('jurusan')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('index_kelas')
                ->requiredMapping()
                ->rules(['required', 'max:5']),
        ];
    }

    public function resolveRecord(): ?Kelas
    {
        $jurusan = Jurusan::where('jurusan', $this->data['jurusan'])->first();

        if (! $jurusan) {
            throw new RowImportFailedException("Jurusan '{$this->data['jurusan']}' tidak ditemukan. Impor data jurusan terlebih dahulu.");
        }

        $exists = Kelas::where('tingkat', $this->data['tingkat'])
            ->where('id_jurusan', $jurusan->id)
            ->where('index_kelas', $this->data['index_kelas'])
            ->exists();

        if ($exists) {
            throw new RowImportFailedException('Kelas ini sudah terdaftar.');
        }

        $this->data['id_jurusan'] = $jurusan->id;
        unset($this->data['jurusan']);

        return new Kelas;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data kelas selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal (duplikat/tidak valid).';
        }

        return $body;
    }
}
