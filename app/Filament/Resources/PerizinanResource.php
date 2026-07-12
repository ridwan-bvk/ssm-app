<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasPerizinanApprovalActions;
use App\Filament\Resources\PerizinanResource\Pages;
use App\Models\Perizinan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Approval queue for izin/sakit requests submitted via the public portal
 * (Phase 2's /izin page) — mirrors app/Controllers/Admin/Perizinan.php.
 * Read-mostly: staff approve/reject rather than freely editing requests.
 */
class PerizinanResource extends Resource
{
    use AuthorizesViaPermission;
    use HasPerizinanApprovalActions;

    protected static ?string $model = Perizinan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Pengajuan Izin';

    // Old app's Admin\Perizinan had no permission filter at all (plan §5.2)
    // — gated here to attendance.edit since approving a leave request is
    // fundamentally an attendance-mutating action.
    protected static ?string $permission = 'attendance.edit';

    protected static ?string $modelLabel = 'Pengajuan Izin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->required(),
                Forms\Components\Select::make('tipe_izin')
                    ->options(['Sakit' => 'Sakit', 'Izin' => 'Izin'])
                    ->required(),
                Forms\Components\Textarea::make('alasan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('pemohon')
                    ->label('Pemohon')
                    ->getStateUsing(fn (Perizinan $record): string => $record->siswa?->nama_siswa ?? $record->guru?->nama_guru ?? '-'),
                Tables\Columns\TextColumn::make('tipe_izin')
                    ->label('Tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date(),
                Tables\Columns\TextColumn::make('alasan')
                    ->limit(40),
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
            ->actions([
                ...static::perizinanApprovalActions(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerizinans::route('/'),
        ];
    }
}
