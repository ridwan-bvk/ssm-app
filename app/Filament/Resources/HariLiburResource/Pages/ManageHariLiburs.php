<?php

namespace App\Filament\Resources\HariLiburResource\Pages;

use App\Filament\Resources\HariLiburResource;
use App\Models\HariLibur;
use App\Support\WorkingDays;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;

class ManageHariLiburs extends ManageRecords
{
    protected static string $resource = HariLiburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateWeekend')
                ->label('Generate Libur Akhir Pekan')
                ->icon('heroicon-o-calendar')
                ->action(function (): void {
                    $count = $this->generateWeekend();

                    Notification::make()
                        ->title("{$count} hari non-kerja berhasil ditambahkan untuk bulan ini.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Mirrors Admin\Holiday::generateWeekend() from the CI4 app: materializes
     * every non-working day (per general_settings.hari_kerja) in the current
     * calendar month into tb_hari_libur, skipping dates already present.
     */
    private function generateWeekend(): int
    {
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
        $month = Carbon::now()->startOfMonth();
        $count = 0;

        for ($date = $month->copy(); $date->month === $month->month; $date->addDay()) {
            if (WorkingDays::isWorkingDay($date)) {
                continue;
            }

            if (HariLibur::whereDate('tanggal', $date->toDateString())->exists()) {
                continue;
            }

            HariLibur::create([
                'tanggal' => $date->toDateString(),
                'keterangan' => 'Hari '.$dayNames[$date->dayOfWeek],
            ]);
            $count++;
        }

        return $count;
    }
}
