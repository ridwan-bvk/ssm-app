<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Siswa;
use App\Services\WaliKelasResolver;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Mirrors the "topLateStudents" widget in Teacher\Dashboard::index(),
 * scoped to the teacher's own class.
 */
class TeacherTopLateStudents extends BaseWidget
{
    protected static ?string $heading = 'Top 5 Siswa Terlambat di Kelas Anda';

    public function table(Table $table): Table
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        return $table
            ->query(
                Siswa::query()
                    ->when($kelas, fn ($q) => $q->where('id_kelas', $kelas->id_kelas), fn ($q) => $q->whereRaw('1 = 0'))
                    ->where('poin_pelanggaran', '>', 0)
                    ->orderByDesc('poin_pelanggaran')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
                Tables\Columns\TextColumn::make('poin_pelanggaran')->label('Poin'),
            ])
            ->paginated(false);
    }
}
