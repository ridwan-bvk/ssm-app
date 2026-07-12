<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Concerns\AuthorizesViaRole;
use App\Filament\Concerns\HasPerizinanApprovalActions;
use App\Models\Perizinan;
use App\Services\WaliKelasResolver;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Mirrors Teacher\Perizinan from the CI4 app: leave/sick requests for
 * students in the teacher's own class only. The old app's konfirmasi()
 * trusted the client-posted id_perizinan without verifying it belonged to
 * a student in the caller's class (migration plan §5.1) — this port
 * re-verifies ownership server-side before ever approving/rejecting.
 */
class TeacherPerizinan extends Page implements HasTable
{
    use AuthorizesViaRole;
    use HasPerizinanApprovalActions;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Pengajuan Izin';

    protected static string $view = 'filament.teacher.pages.teacher-perizinan';

    protected static ?string $requiredRole = 'guru';

    public function table(Table $table): Table
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        return $table
            ->query(
                Perizinan::query()
                    ->whereHas('siswa', fn ($q) => $kelas
                        ? $q->where('id_kelas', $kelas->id_kelas)
                        : $q->whereRaw('1 = 0'))
                    ->orderByDesc('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('siswa.nama_siswa')->label('Nama Siswa'),
                Tables\Columns\TextColumn::make('tipe_izin')->label('Tipe')->badge(),
                Tables\Columns\TextColumn::make('tanggal_mulai')->label('Mulai')->date(),
                Tables\Columns\TextColumn::make('tanggal_selesai')->label('Selesai')->date(),
                Tables\Columns\TextColumn::make('alasan')->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['Pending' => 'Pending', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak']),
            ])
            ->actions(static::perizinanApprovalActions(
                authorize: fn (Perizinan $record) => $kelas && $record->siswa?->id_kelas === $kelas->id_kelas,
            ));
    }
}
