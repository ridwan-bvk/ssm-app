<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Siswa;
use App\Services\AttendanceStatusResolver;
use App\Services\WaliKelasResolver;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Mirrors the "absenteeAlerts" widget in Teacher\Dashboard::index(),
 * scoped to the teacher's own class.
 */
class TeacherAbsenteeAlerts extends BaseWidget
{
    protected static ?string $heading = 'Peringatan Ketidakhadiran Beruntun';

    public function table(Table $table): Table
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());
        $ids = $kelas
            ? app(AttendanceStatusResolver::class)->consecutiveAbsences(3, $kelas->id_kelas)->pluck('id_siswa')
            : collect();

        return $table
            ->query(Siswa::query()->whereIn('id_siswa', $ids))
            ->columns([
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada siswa dengan ketidakhadiran beruntun');
    }
}
