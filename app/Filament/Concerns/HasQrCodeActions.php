<?php

namespace App\Filament\Concerns;

use App\Services\QrService;
use Closure;
use Filament\Actions\Action as HeaderAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;

/**
 * Shared "generate / download / print" QR action triad used by QrGuru,
 * QrSiswa, and Teacher\Pages\TeacherQr. Each caller supplies model-specific
 * closures (which record to generate for, which route to print, which
 * records to bulk-zip) since the trio otherwise differs only in that.
 */
trait HasQrCodeActions
{
    /** @param  Closure($record): string  $generate */
    protected static function qrGenerateAction(Closure $generate): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate')
            ->label('Generate')
            ->icon('heroicon-o-qr-code')
            ->action(function ($record) use ($generate): void {
                $generate($record);
                Notification::make()->title('QR Code berhasil dibuat')->success()->send();
            });
    }

    /**
     * @param  Closure($record): string  $generate
     * @param  Closure($record): bool|null  $authorize
     */
    protected static function qrDownloadAction(Closure $generate, ?Closure $authorize = null): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('download')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($record) use ($generate, $authorize) {
                if ($authorize && ! $authorize($record)) {
                    abort(403);
                }

                return Storage::disk('public')->download($generate($record));
            });
    }

    /** @param  Closure($record): string  $url */
    protected static function qrPrintAction(Closure $url): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('print')
            ->label('Cetak')
            ->icon('heroicon-o-printer')
            ->url(fn ($record) => $url($record))
            ->openUrlInNewTab();
    }

    /**
     * @param  Closure(): iterable  $records
     * @param  Closure($record): string  $generate
     */
    protected static function qrDownloadAllAction(Closure $records, Closure $generate, Closure|string $zipFolder, string $zipFilename): HeaderAction
    {
        return HeaderAction::make('downloadAll')
            ->label('Download Semua (ZIP)')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->action(function () use ($records, $generate, $zipFolder, $zipFilename) {
                foreach ($records() as $record) {
                    $generate($record);
                }

                $zipPath = app(QrService::class)->zipFolder(value($zipFolder), $zipFilename);

                return response()->download($zipPath)->deleteFileAfterSend();
            });
    }

    protected static function qrPrintAllAction(Closure|string $url): HeaderAction
    {
        return HeaderAction::make('printAll')
            ->label('Cetak Semua')
            ->icon('heroicon-o-printer')
            ->url(fn () => value($url))
            ->openUrlInNewTab();
    }
}
