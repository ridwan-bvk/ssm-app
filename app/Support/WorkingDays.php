<?php

namespace App\Support;

use App\Models\GeneralSetting;
use Carbon\CarbonInterface;

/**
 * Mirrors the global isWorkingDay() helper in the CI4 app's app/Common.php:
 * general_settings.hari_kerja is a CSV of ISO weekday numbers (1=Mon..7=Sun).
 * Kept intentionally independent from HariLibur (explicit holiday dates) —
 * the two are separate, not-fully-synchronized mechanisms in the old app too.
 */
class WorkingDays
{
    public static function isWorkingDay(CarbonInterface $date): bool
    {
        $settings = GeneralSetting::first();
        $hariKerja = $settings?->hari_kerja;

        if (filled($hariKerja)) {
            $workingDays = array_map('trim', explode(',', $hariKerja));

            return in_array((string) $date->isoWeekday(), $workingDays, true);
        }

        return $date->isoWeekday() <= 5;
    }
}
