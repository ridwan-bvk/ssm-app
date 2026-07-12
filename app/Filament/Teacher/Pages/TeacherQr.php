<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Concerns\AuthorizesViaRole;
use App\Filament\Concerns\HasQrCodeActions;
use App\Models\Siswa;
use App\Services\QrService;
use App\Services\WaliKelasResolver;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Mirrors Teacher\QRCode from the CI4 app: generate/download/print QR
 * codes, scoped to the teacher's own class (server-derived, never a
 * client-supplied id_kelas).
 */
class TeacherQr extends Page implements HasTable
{
    use AuthorizesViaRole;
    use HasQrCodeActions;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR Code Siswa';

    protected static string $view = 'filament.teacher.pages.teacher-qr';

    protected static ?string $requiredRole = 'guru';

    private static function generate(Siswa $siswa): string
    {
        return app(QrService::class)->generateForSiswa($siswa);
    }

    public function table(Table $table): Table
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        return $table
            ->query(Siswa::query()->when($kelas, fn ($q) => $q->where('id_kelas', $kelas->id_kelas), fn ($q) => $q->whereRaw('1 = 0'))->orderBy('nama_siswa'))
            ->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS'),
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
            ])
            ->actions([
                static::qrDownloadAction(
                    generate: fn (Siswa $record) => static::generate($record),
                    authorize: fn (Siswa $record) => $kelas && $record->id_kelas === $kelas->id_kelas,
                ),
                static::qrPrintAction(fn (Siswa $record) => route('teacher.qr.print.siswa.single', $record)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        if (! $kelas) {
            return [];
        }

        return [
            static::qrDownloadAllAction(
                records: fn () => Siswa::where('id_kelas', $kelas->id_kelas)->get(),
                generate: fn (Siswa $record) => static::generate($record),
                zipFolder: 'qr-siswa/'.app(QrService::class)->kelasSlug($kelas),
                zipFilename: 'qrcode-siswa.zip',
            ),
            static::qrPrintAllAction(route('teacher.qr.print.siswa')),
        ];
    }
}
