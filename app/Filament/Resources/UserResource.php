<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesViaRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Guru;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * "Petugas" (staff/scanner-operator accounts) in the old CI4 app — this
 * manages the same `users` table but is scoped to superadmin, mirroring
 * app/Controllers/Admin/DataPetugas.php's every-action is_superadmin() guard.
 */
class UserResource extends Resource
{
    use AuthorizesViaRole;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Data Petugas';

    protected static ?string $modelLabel = 'Petugas';

    protected static ?string $requiredRole = 'superadmin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Username')
                    ->required()
                    ->minLength(6)
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->minLength(6)
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                Forms\Components\Select::make('role')
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Staf Petugas',
                        'kepsek' => 'Kepala Sekolah',
                        'scanner' => 'Scanner',
                    ])
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?User $record) {
                        if ($record) {
                            $component->state($record->roles->pluck('name')->first(fn ($r) => $r !== 'guru'));
                        }
                    }),
                Forms\Components\Select::make('id_guru')
                    ->label('Hubungkan ke Guru')
                    ->options(fn () => Guru::pluck('nama_guru', 'id_guru'))
                    ->searchable()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                Tables\Columns\TextColumn::make('guru.nama_guru')
                    ->label('Guru Terhubung')
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (User $record, array $data): void {
                        $roles = [$data['role']];
                        if ($record->id_guru) {
                            $roles[] = 'guru';
                        }
                        $record->syncRoles($roles);
                    }),
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
