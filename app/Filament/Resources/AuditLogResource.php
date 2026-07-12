<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only viewer for tb_audit_logs, mirroring Admin\Dashboard::auditLog()
 * from the CI4 app. That controller action had no permission gate at all
 * (plan §5.2) and its trail simply wasn't viewable anywhere in the new
 * panel until now — this resource closes both gaps at once.
 */
class AuditLogResource extends Resource
{
    use AuthorizesViaPermission;

    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $permission = 'audit.view';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('user.name')->label('Pengguna')->default('-'),
            TextEntry::make('aksi')->label('Aksi'),
            TextEntry::make('tabel')->label('Tabel'),
            TextEntry::make('id_record')->label('ID Record'),
            TextEntry::make('ip_address')->label('IP Address'),
            TextEntry::make('created_at')->label('Waktu')->dateTime(),
            TextEntry::make('data_lama')->label('Data Lama')->columnSpanFull()->formatStateUsing(
                fn (?string $state): string => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '-'
            ),
            TextEntry::make('data_baru')->label('Data Baru')->columnSpanFull()->formatStateUsing(
                fn (?string $state): string => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '-'
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tabel')
                    ->label('Tabel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_record')
                    ->label('ID Record'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tabel')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->pluck('tabel', 'tabel')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
