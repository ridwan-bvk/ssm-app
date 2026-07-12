<?php

namespace App\Filament\Concerns;

use App\Models\Perizinan;
use App\Services\LeaveApprovalService;
use Closure;
use Filament\Notifications\Notification;
use Filament\Tables;

trait HasPerizinanApprovalActions
{
    /**
     * @param  Closure(Perizinan): bool|null  $authorize
     * @return Tables\Actions\Action[]
     */
    protected static function perizinanApprovalActions(?Closure $authorize = null): array
    {
        return [
            static::perizinanConfirmAction('setujui', 'Setujui', 'Disetujui', 'success', 'heroicon-o-check', $authorize),
            static::perizinanConfirmAction('tolak', 'Tolak', 'Ditolak', 'danger', 'heroicon-o-x-mark', $authorize),
        ];
    }

    protected static function perizinanConfirmAction(string $name, string $label, string $status, string $color, string $icon, ?Closure $authorize): Tables\Actions\Action
    {
        return Tables\Actions\Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Perizinan $record): bool => $record->status === 'Pending')
            ->requiresConfirmation()
            ->action(function (Perizinan $record) use ($status, $authorize): void {
                if ($authorize && ! $authorize($record)) {
                    Notification::make()->title('Pengajuan ini bukan bagian dari kelas Anda')->danger()->send();

                    return;
                }

                app(LeaveApprovalService::class)->confirm($record, $status, auth()->id());
                Notification::make()
                    ->title($status === 'Disetujui' ? 'Pengajuan disetujui' : 'Pengajuan ditolak')
                    ->color($status === 'Disetujui' ? 'success' : 'danger')
                    ->send();
            });
    }
}
