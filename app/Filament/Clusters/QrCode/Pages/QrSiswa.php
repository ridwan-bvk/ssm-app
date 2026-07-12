<?php

namespace App\Filament\Clusters\QrCode\Pages;

use App\Filament\Clusters\QrCode;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasKelasOptions;
use App\Filament\Concerns\HasQrCodeActions;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\QrService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Mirrors Admin\GenerateQR + the siswa-facing parts of Admin\QRGenerator
 * from the CI4 app: generate/download per-student QR, download a whole
 * class as a zip, or open the 4-column print view.
 */
class QrSiswa extends Page implements HasTable
{
    use AuthorizesViaPermission;
    use HasKelasOptions;
    use HasQrCodeActions;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR Code Siswa';

    protected static ?string $cluster = QrCode::class;

    protected static string $view = 'filament.pages.qr-siswa';

    protected static ?string $permission = 'qr.generate';

    private static function generate(Siswa $siswa): string
    {
        return app(QrService::class)->generateForSiswa($siswa);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Siswa::query()->orderBy('nama_siswa'))
            ->filters([
                Tables\Filters\SelectFilter::make('id_kelas')
                    ->label('Kelas')
                    ->options(fn () => static::kelasOptions()),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS'),
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
                Tables\Columns\TextColumn::make('kelas.tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn (Siswa $record) => static::kelasLabel($record->kelas)),
            ])
            ->actions([
                static::qrGenerateAction(fn (Siswa $record) => static::generate($record)),
                static::qrDownloadAction(fn (Siswa $record) => static::generate($record)),
                static::qrPrintAction(fn (Siswa $record) => route('qr.print.siswa.single', $record)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            static::qrDownloadAllAction(
                records: function () {
                    $idKelas = $this->tableFilters['id_kelas']['value'] ?? null;

                    return Siswa::when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))->get();
                },
                generate: fn (Siswa $record) => static::generate($record),
                zipFolder: function () {
                    $idKelas = $this->tableFilters['id_kelas']['value'] ?? null;

                    return $idKelas
                        ? 'qr-siswa/'.app(QrService::class)->kelasSlug(Kelas::find($idKelas))
                        : 'qr-siswa';
                },
                zipFilename: 'qrcode-siswa.zip',
            ),
            static::qrPrintAllAction(function () {
                $idKelas = $this->tableFilters['id_kelas']['value'] ?? null;

                return route('qr.print.siswa', $idKelas ? ['id_kelas' => $idKelas] : []);
            }),
        ];
    }
}
