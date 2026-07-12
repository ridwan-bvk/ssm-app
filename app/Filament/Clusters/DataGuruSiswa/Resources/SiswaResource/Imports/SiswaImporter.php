<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources\SiswaResource\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Mirrors SiswaModel::importCSVItem() from the CI4 app: duplicate NIS is
 * rejected (not updated), unrecognized id_kelas is rejected, and gender is
 * normalized from the same set of accepted input strings.
 */
class SiswaImporter extends Importer
{
    protected static ?string $model = Siswa::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nis')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('nama_siswa')
                ->requiredMapping()
                ->rules(['required', 'min:3', 'max:255']),
            ImportColumn::make('id_kelas')
                ->requiredMapping()
                ->rules(['required', 'integer']),
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

    public function resolveRecord(): ?Siswa
    {
        if (! Kelas::where('id_kelas', $this->data['id_kelas'])->exists()) {
            throw new RowImportFailedException("ID Kelas {$this->data['id_kelas']} tidak ditemukan.");
        }

        if (Siswa::where('nis', $this->data['nis'])->exists()) {
            throw new RowImportFailedException("NIS {$this->data['nis']} sudah terdaftar.");
        }

        return new Siswa;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data siswa selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal (duplikat/tidak valid).';
        }

        return $body;
    }
}
