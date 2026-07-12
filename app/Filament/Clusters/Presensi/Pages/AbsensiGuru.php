<?php

namespace App\Filament\Clusters\Presensi\Pages;

use App\Filament\Clusters\Presensi;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasAttendanceEditModal;
use App\Models\Guru;
use App\Services\AttendanceEditService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Mirrors Admin\DataAbsenGuru from the CI4 app: pick a date, see every
 * teacher's attendance for that day, edit any row via a modal.
 */
class AbsensiGuru extends Page implements HasTable
{
    use AuthorizesViaPermission;
    use HasAttendanceEditModal;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Absensi Guru';

    protected static ?string $cluster = Presensi::class;

    protected static string $view = 'filament.pages.absensi-guru';

    protected static ?string $permission = 'attendance.edit';

    public function table(Table $table): Table
    {
        $tanggal = $this->tableFilters['tanggal']['value'] ?? Carbon::today()->toDateString();

        return $table
            ->query(Guru::query()->orderBy('nama_guru'))
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([Forms\Components\DatePicker::make('value')->label('Tanggal')->default(Carbon::today())])
                    ->indicateUsing(fn (array $data): ?string => $data['value'] ? 'Tanggal: '.$data['value'] : null),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->columns([
                Tables\Columns\TextColumn::make('nuptk')->label('NUPTK'),
                Tables\Columns\TextColumn::make('nama_guru')->label('Nama'),
                ...static::attendanceStatusColumns(fn () => $tanggal),
            ])
            ->actions([
                static::attendanceEditAction(
                    tanggal: fn () => $tanggal,
                    updateCallback: fn ($record, array $data, string $t) => app(AttendanceEditService::class)->updateGuru(
                        $record->id_guru,
                        $t,
                        (int) $data['id_kehadiran'],
                        $data['jam_masuk'],
                        $data['jam_keluar'],
                        $data['keterangan'],
                        $record->nama_guru,
                    ),
                ),
            ]);
    }
}
