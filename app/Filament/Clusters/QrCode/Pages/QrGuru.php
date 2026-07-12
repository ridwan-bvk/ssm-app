<?php

namespace App\Filament\Clusters\QrCode\Pages;

use App\Filament\Clusters\QrCode;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasQrCodeActions;
use App\Models\Guru;
use App\Services\QrService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Mirrors Admin\GenerateQR + the guru-facing parts of Admin\QRGenerator
 * from the CI4 app.
 */
class QrGuru extends Page implements HasTable
{
    use AuthorizesViaPermission;
    use HasQrCodeActions;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR Code Guru';

    protected static ?string $cluster = QrCode::class;

    protected static string $view = 'filament.pages.qr-guru';

    protected static ?string $permission = 'qr.generate';

    private static function generate(Guru $guru): string
    {
        return app(QrService::class)->generateForGuru($guru);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Guru::query()->orderBy('nama_guru'))
            ->columns([
                Tables\Columns\TextColumn::make('nuptk')->label('NUPTK'),
                Tables\Columns\TextColumn::make('nama_guru')->label('Nama'),
            ])
            ->actions([
                static::qrGenerateAction(fn (Guru $record) => static::generate($record)),
                static::qrDownloadAction(fn (Guru $record) => static::generate($record)),
                static::qrPrintAction(fn (Guru $record) => route('qr.print.guru.single', $record)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            static::qrDownloadAllAction(
                records: fn () => Guru::all(),
                generate: fn (Guru $record) => static::generate($record),
                zipFolder: 'qr-guru',
                zipFilename: 'qrcode-guru.zip',
            ),
            static::qrPrintAllAction(route('qr.print.guru')),
        ];
    }
}
