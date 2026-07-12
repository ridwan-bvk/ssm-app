<?php

namespace App\Filament\Clusters\KelasJurusan\Resources;

use App\Filament\Clusters\KelasJurusan;
use App\Filament\Clusters\KelasJurusan\Resources\JurusanResource\Pages;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Models\Jurusan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JurusanResource extends Resource
{
    use AuthorizesViaPermission;

    protected static ?string $model = Jurusan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = KelasJurusan::class;

    protected static ?string $permission = 'classes.manage';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jurusan')
                    ->label('Nama Jurusan')
                    ->required()
                    ->maxLength(32)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jurusan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Jurusan $record): bool => $record->kelas()->exists())
                    ->tooltip(fn (Jurusan $record): ?string => $record->kelas()->exists()
                        ? 'Tidak dapat dihapus karena masih memiliki kelas'
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
            'index' => Pages\ManageJurusans::route('/'),
        ];
    }
}
