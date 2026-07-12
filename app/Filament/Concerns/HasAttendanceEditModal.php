<?php

namespace App\Filament\Concerns;

use App\Models\Kehadiran;
use App\Services\AttendanceStatusResolver;
use Closure;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

/**
 * Shared "per-day attendance list + edit modal" behaviour used by
 * AbsensiGuru, AbsensiSiswa, and Teacher\Pages\TeacherAttendance. The
 * teacher variant additionally re-verifies class ownership server-side
 * before writing (defence in depth) — accommodated here via the optional
 * $visible/$authorizeRecord closures rather than forking the trait.
 */
trait HasAttendanceEditModal
{
    /** @return Tables\Columns\TextColumn[] */
    protected static function attendanceStatusColumns(Closure $tanggal): array
    {
        return [
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->getStateUsing(function ($record) use ($tanggal): string {
                    $t = $tanggal();
                    $presensi = $record->presensi()->whereDate('tanggal', $t)->first();

                    return $presensi?->kehadiran?->kehadiran
                        ?? (app(AttendanceStatusResolver::class)->isAfterSchool($t) ? 'Alfa' : 'Belum Scan');
                })
                ->badge(),
            Tables\Columns\TextColumn::make('jam_masuk')
                ->label('Jam Masuk')
                ->getStateUsing(fn ($record) => $record->presensi()->whereDate('tanggal', $tanggal())->first()?->jam_masuk ?? '-'),
            Tables\Columns\TextColumn::make('jam_keluar')
                ->label('Jam Keluar')
                ->getStateUsing(fn ($record) => $record->presensi()->whereDate('tanggal', $tanggal())->first()?->jam_keluar ?? '-'),
        ];
    }

    /**
     * @param  Closure(): string  $tanggal
     * @param  Closure($record, array $data, string $tanggal): void  $updateCallback
     * @param  Closure(): bool|null  $visible
     * @param  Closure($record): bool|null  $authorizeRecord
     */
    protected static function attendanceEditAction(
        Closure $tanggal,
        Closure $updateCallback,
        ?Closure $visible = null,
        ?Closure $authorizeRecord = null,
        string $unauthorizedMessage = 'Data ini bukan bagian dari kelas Anda',
    ): Tables\Actions\Action {
        $action = Tables\Actions\Action::make('ubah')
            ->label('Ubah Kehadiran')
            ->icon('heroicon-o-pencil-square')
            ->form([
                Forms\Components\Select::make('id_kehadiran')
                    ->label('Status Kehadiran')
                    ->options(fn () => Kehadiran::pluck('kehadiran', 'id_kehadiran'))
                    ->required(),
                Forms\Components\TimePicker::make('jam_masuk')->label('Jam Masuk'),
                Forms\Components\TimePicker::make('jam_keluar')->label('Jam Keluar'),
                Forms\Components\TextInput::make('keterangan')->label('Keterangan'),
            ])
            ->fillForm(function ($record) use ($tanggal): array {
                $presensi = $record->presensi()->whereDate('tanggal', $tanggal())->first();

                return [
                    'id_kehadiran' => $presensi?->id_kehadiran ?? Kehadiran::TANPA_KETERANGAN,
                    'jam_masuk' => $presensi?->jam_masuk,
                    'jam_keluar' => $presensi?->jam_keluar,
                    'keterangan' => $presensi?->keterangan,
                ];
            })
            ->action(function ($record, array $data) use ($tanggal, $updateCallback, $authorizeRecord, $unauthorizedMessage): void {
                if ($authorizeRecord && ! $authorizeRecord($record)) {
                    Notification::make()->title($unauthorizedMessage)->danger()->send();

                    return;
                }

                $updateCallback($record, $data, $tanggal());

                Notification::make()->title('Kehadiran berhasil diubah')->success()->send();
            });

        return $visible ? $action->visible($visible) : $action;
    }
}
