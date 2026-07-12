<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Concerns\AuthorizesViaRole;
use App\Filament\Concerns\HasAttendanceEditModal;
use App\Models\Siswa;
use App\Services\AttendanceEditService;
use App\Services\WaliKelasResolver;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Mirrors Teacher\Dashboard::attendance()/getAttendanceList()/getEditModal()/
 * updateSingleAttendance() from the CI4 app. The class is ALWAYS re-derived
 * from the logged-in teacher via WaliKelasResolver — never trusts a
 * client-supplied id_kelas, closing the IDOR gap the old app had here
 * (see migration plan §5.1).
 */
class TeacherAttendance extends Page implements HasTable
{
    use AuthorizesViaRole;
    use HasAttendanceEditModal;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Kehadiran Siswa';

    protected static string $view = 'filament.teacher.pages.teacher-attendance';

    protected static ?string $requiredRole = 'guru';

    public function table(Table $table): Table
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());
        $tanggal = $this->tableFilters['tanggal']['value'] ?? Carbon::today()->toDateString();

        return $table
            ->query(Siswa::query()
                ->when($kelas, fn ($q) => $q->where('id_kelas', $kelas->id_kelas), fn ($q) => $q->whereRaw('1 = 0'))
                ->orderBy('nama_siswa'))
            ->filters([
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
                // Re-verifies the record actually belongs to this teacher's
                // class server-side, even though the base query already
                // filters by it — defence in depth against the IDOR pattern
                // the old app had here.
                static::attendanceEditAction(
                    tanggal: fn () => $tanggal,
                    updateCallback: fn ($record, array $data, string $t) => app(AttendanceEditService::class)->updateSiswa(
                        $record->id_siswa,
                        $kelas->id_kelas,
                        $t,
                        (int) $data['id_kehadiran'],
                        $data['jam_masuk'],
                        $data['jam_keluar'],
                        $data['keterangan'],
                        $record->nama_siswa,
                    ),
                    visible: fn () => $kelas !== null,
                    authorizeRecord: fn ($record) => $kelas && $record->id_kelas === $kelas->id_kelas,
                    unauthorizedMessage: 'Siswa ini bukan bagian dari kelas Anda',
                ),
            ]);
    }
}
