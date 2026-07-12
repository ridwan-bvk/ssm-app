<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasKelasOptions;
use App\Models\Siswa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Mirrors the "topLateStudents" widget from Admin\Dashboard::index() in the
 * CI4 app: top 5 students by cumulative lateness points (poin_pelanggaran).
 */
class TopLateStudents extends BaseWidget
{
    use HasKelasOptions;

    protected static ?string $heading = 'Top 5 Siswa Terlambat';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Siswa::query()
                    ->where('poin_pelanggaran', '>', 0)
                    ->orderByDesc('poin_pelanggaran')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
                Tables\Columns\TextColumn::make('kelas.tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn (Siswa $record) => static::kelasLabel($record->kelas)),
                Tables\Columns\TextColumn::make('poin_pelanggaran')->label('Poin'),
            ])
            ->paginated(false);
    }
}
