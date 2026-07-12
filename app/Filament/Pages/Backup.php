<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesViaPermission;
use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors Admin\Backup from the CI4 app: separate DB backup/restore and
 * uploads (photos/QR) backup/restore actions.
 */
class Backup extends Page
{
    use AuthorizesViaPermission;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Backup & Restore';

    protected static string $view = 'filament.pages.backup';

    protected static ?string $permission = 'backup.manage';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDb')
                ->label('Backup Database')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => response()->download(app(BackupService::class)->dumpDatabase())->deleteFileAfterSend()),

            Action::make('restoreDb')
                ->label('Restore Database')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Merestore database akan menimpa data yang ada saat ini. Lanjutkan?')
                ->form([
                    FileUpload::make('file')
                        ->label('File .sql')
                        ->disk('local')
                        ->directory('tmp')
                        ->acceptedFileTypes(['application/sql', 'text/plain', 'application/octet-stream'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);

                    try {
                        app(BackupService::class)->restoreDatabase($path);
                        Notification::make()->title('Database berhasil direstore')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal merestore database')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('downloadPhotos')
                ->label('Backup Foto/Upload')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => response()->download(app(BackupService::class)->zipUploads())->deleteFileAfterSend()),

            Action::make('restorePhotos')
                ->label('Restore Foto/Upload')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('File dengan nama sama akan ditimpa. Lanjutkan?')
                ->form([
                    FileUpload::make('file')
                        ->label('File .zip')
                        ->disk('local')
                        ->directory('tmp')
                        ->acceptedFileTypes(['application/zip'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);

                    try {
                        app(BackupService::class)->restoreUploadsZip($path);
                        Notification::make()->title('Foto/upload berhasil direstore')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal merestore foto')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
