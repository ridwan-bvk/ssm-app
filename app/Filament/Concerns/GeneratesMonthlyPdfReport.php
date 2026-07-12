<?php

namespace App\Filament\Concerns;

use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

trait GeneratesMonthlyPdfReport
{
    /**
     * @param  array<string, mixed>  $report
     */
    protected static function downloadMonthlyReportPdf(array $report, string $emptyKey, string $emptyMessage, string $view, string $filename): mixed
    {
        /** @var Collection $collection */
        $collection = $report[$emptyKey];

        if ($collection->isEmpty()) {
            Notification::make()->title($emptyMessage)->danger()->send();

            return null;
        }

        $pdf = Pdf::loadView($view, [...$report, 'generalSettings' => GeneralSetting::first()]);

        return $pdf->download($filename);
    }
}
