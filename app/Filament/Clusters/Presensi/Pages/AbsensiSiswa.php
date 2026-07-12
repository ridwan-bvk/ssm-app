<?php

namespace App\Filament\Clusters\Presensi\Pages;

use App\Filament\Clusters\Presensi;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasAttendanceEditModal;
use App\Filament\Concerns\HasKelasOptions;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\AttendanceEditService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Mirrors Admin\DataAbsenSiswa from the CI4 app: pick a class + date, see
 * every student's attendance for that day (or "Belum Scan"/"Alfa" if none),
 * edit any row via a modal. Every edit writes an audit log entry.
 */
class AbsensiSiswa extends Page implements HasTable
{
    use AuthorizesViaPermission;
    use HasAttendanceEditModal;
    use HasKelasOptions;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Absensi Siswa';

    protected static ?string $cluster = Presensi::class;

    protected static string $view = 'filament.pages.absensi-siswa';

    protected static ?string $permission = 'attendance.edit';

    public function table(Table $table): Table
    {
        $idKelas = $this->tableFilters['id_kelas']['value'] ?? Kelas::query()->orderBy('id_kelas')->value('id_kelas');
        $tanggal = $this->tableFilters['tanggal']['value'] ?? Carbon::today()->toDateString();

        return $table
            ->query(Siswa::query()->when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))->orderBy('nama_siswa'))
            ->filters([
                Tables\Filters\SelectFilter::make('id_kelas')
                    ->label('Kelas')
                    ->options(fn () => static::kelasOptions()),
                Tables\Filters\Filter::make('tanggal')
                    ->form([Forms\Components\DatePicker::make('value')->label('Tanggal')->default(Carbon::today())])
                    ->indicateUsing(fn (array $data): ?string => $data['value'] ? 'Tanggal: '.$data['value'] : null),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS'),
                Tables\Columns\TextColumn::make('nama_siswa')->label('Nama'),
                ...static::attendanceStatusColumns(fn () => $tanggal),
            ])
            ->actions([
                static::attendanceEditAction(
                    tanggal: fn () => $tanggal,
                    updateCallback: fn ($record, array $data, string $t) => app(AttendanceEditService::class)->updateSiswa(
                        $record->id_siswa,
                        $record->id_kelas,
                        $t,
                        (int) $data['id_kehadiran'],
                        $data['jam_masuk'],
                        $data['jam_keluar'],
                        $data['keterangan'],
                        $record->nama_siswa,
                    ),
                ),
            ]);
    }
}
