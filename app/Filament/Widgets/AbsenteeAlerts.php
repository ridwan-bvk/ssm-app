<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasKelasOptions;
use App\Models\Siswa;
use App\Services\AttendanceStatusResolver;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Mirrors the "absenteeAlerts" widget from Admin\Dashboard::index() in the
 * CI4 app: students absent 3+ days running with zero excused entries, for
 * early detection.
 */
class AbsenteeAlerts extends BaseWidget
{
    use HasKelasOptions;

    protected static ?string $heading = 'Peringatan Ketidakhadiran Beruntun';

    public function table(Table $table): Table
    {
        $ids = app(AttendanceStatusResolver::class)->consecutiveAbsences(3)->pluck('id_siswa');

        return $table
            ->query(Siswa::query()->whereIn('id_siswa', $ids))
            ->columns([
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
                Tables\Columns\TextColumn::make('kelas.tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn (Siswa $record) => static::kelasLabel($record->kelas)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada siswa dengan ketidakhadiran beruntun');
    }
}
