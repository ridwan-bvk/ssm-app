<?php

namespace App\Filament\Clusters\KelasJurusan\Resources;

use App\Filament\Clusters\KelasJurusan;
use App\Filament\Clusters\KelasJurusan\Resources\KelasResource\Pages;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    use AuthorizesViaPermission;

    protected static ?string $model = Kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = KelasJurusan::class;

    protected static ?string $permission = 'classes.manage';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tingkat')
                    ->options(['X' => 'X', 'XI' => 'XI', 'XII' => 'XII'])
                    ->required(),
                Forms\Components\Select::make('id_jurusan')
                    ->label('Jurusan')
                    ->options(fn () => Jurusan::pluck('jurusan', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('index_kelas')
                    ->label('Indeks Kelas')
                    ->required()
                    ->maxLength(5),
                Forms\Components\Select::make('id_wali_kelas')
                    ->label('Wali Kelas')
                    ->options(fn () => Guru::pluck('nama_guru', 'id_guru'))
                    ->searchable()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tingkat')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jurusan.jurusan')
                    ->label('Jurusan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('index_kelas')
                    ->label('Indeks'),
                Tables\Columns\TextColumn::make('waliKelas.nama_guru')
                    ->label('Wali Kelas')
                    ->placeholder('Belum ditugaskan'),
                Tables\Columns\TextColumn::make('siswa_count')
                    ->label('Jumlah Siswa')
                    ->counts('siswa'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Kelas $record): bool => $record->siswa()->exists())
                    ->tooltip(fn (Kelas $record): ?string => $record->siswa()->exists()
                        ? 'Tidak dapat dihapus karena masih memiliki siswa'
                        : null),
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
            'index' => Pages\ManageKelas::route('/'),
        ];
    }
}
