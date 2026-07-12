<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources;

use App\Filament\Clusters\DataGuruSiswa;
use App\Filament\Clusters\DataGuruSiswa\Resources\SiswaResource\Pages;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\HasKelasOptions;
use App\Models\Siswa;
use App\Rules\UniqueRfid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiswaResource extends Resource
{
    use AuthorizesViaPermission;
    use HasKelasOptions;

    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = DataGuruSiswa::class;

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $permission = 'students.manage';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nis')
                    ->label('NIS')
                    ->required()
                    ->minLength(4)
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('nama_siswa')
                    ->label('Nama Siswa')
                    ->required()
                    ->minLength(3)
                    ->maxLength(255),
                Forms\Components\Select::make('id_kelas')
                    ->label('Kelas')
                    ->options(fn () => static::kelasOptions())
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan'])
                    ->required(),
                Forms\Components\TextInput::make('no_hp')
                    ->label('No. HP')
                    ->required()
                    ->numeric()
                    ->maxLength(32),
                Forms\Components\TextInput::make('rfid_code')
                    ->label('Kode RFID')
                    ->maxLength(100)
                    ->rule(fn (?Siswa $record) => new UniqueRfid($record?->id_siswa, 'siswa')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_siswa')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kelas.tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn (Siswa $record) => static::kelasLabel($record->kelas))
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('L/P'),
                Tables\Columns\TextColumn::make('no_hp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('poin_pelanggaran')
                    ->label('Poin')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfid_code')
                    ->label('RFID')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_kelas')
                    ->label('Kelas')
                    ->options(fn () => static::kelasOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageSiswas::route('/'),
        ];
    }
}
