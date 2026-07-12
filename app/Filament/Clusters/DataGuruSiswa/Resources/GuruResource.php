<?php

namespace App\Filament\Clusters\DataGuruSiswa\Resources;

use App\Filament\Clusters\DataGuruSiswa;
use App\Filament\Clusters\DataGuruSiswa\Resources\GuruResource\Pages;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Models\Guru;
use App\Rules\UniqueRfid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuruResource extends Resource
{
    use AuthorizesViaPermission;

    protected static ?string $model = Guru::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $cluster = DataGuruSiswa::class;

    protected static ?string $modelLabel = 'Guru';

    protected static ?string $permission = 'teachers.manage';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nuptk')
                    ->label('NUPTK')
                    ->required()
                    ->minLength(16)
                    ->maxLength(24)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('nama_guru')
                    ->label('Nama Guru')
                    ->required()
                    ->minLength(3)
                    ->maxLength(255),
                Forms\Components\Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan'])
                    ->required(),
                Forms\Components\Textarea::make('alamat')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('no_hp')
                    ->label('No. HP')
                    ->required()
                    ->numeric()
                    ->maxLength(32),
                Forms\Components\TextInput::make('rfid_code')
                    ->label('Kode RFID')
                    ->maxLength(100)
                    ->rule(fn (?Guru $record) => new UniqueRfid($record?->id_guru, 'guru')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nuptk')
                    ->label('NUPTK')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_guru')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('L/P'),
                Tables\Columns\TextColumn::make('no_hp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kelasWali.tingkat')
                    ->label('Wali Kelas')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('rfid_code')
                    ->label('RFID')
                    ->toggleable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ManageGurus::route('/'),
        ];
    }
}
