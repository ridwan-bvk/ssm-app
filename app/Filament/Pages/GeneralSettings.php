<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesViaPermission;
use App\Models\GeneralSetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors Admin\GeneralSettings + GeneralSettingsModel from the CI4 app:
 * a single-row settings form (school name/year/logo/limits/workdays).
 * Fixes the old app's logo-cleanup bug (GeneralSettingsModel::updateSettings()
 * checked file_exists() on a relative path missing the FCPATH prefix, so
 * the old logo file was never actually deleted) by using Storage properly.
 */
class GeneralSettings extends Page implements HasForms
{
    use AuthorizesViaPermission;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static string $view = 'filament.pages.general-settings';

    protected static ?string $permission = 'settings.manage';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = GeneralSetting::firstOrCreate(['id' => 1]);

        $this->form->fill([
            ...$settings->toArray(),
            'hari_kerja' => $settings->hari_kerja ? explode(',', $settings->hari_kerja) : [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                TextInput::make('school_name')
                    ->label('Nama Sekolah')
                    ->required()
                    ->maxLength(225),
                TextInput::make('school_year')
                    ->label('Tahun Ajaran')
                    ->required()
                    ->maxLength(225),
                FileUpload::make('logo')
                    ->label('Logo Sekolah')
                    ->image()
                    ->directory('logo')
                    ->disk('public'),
                TimePicker::make('jam_masuk_limit')
                    ->label('Batas Jam Masuk'),
                TimePicker::make('jam_pulang_standard')
                    ->label('Jam Pulang Standar'),
                CheckboxList::make('hari_kerja')
                    ->label('Hari Kerja')
                    ->options([
                        '1' => 'Senin',
                        '2' => 'Selasa',
                        '3' => 'Rabu',
                        '4' => 'Kamis',
                        '5' => "Jum'at",
                        '6' => 'Sabtu',
                        '7' => 'Minggu',
                    ])
                    ->columns(4),
                TextInput::make('copyright')
                    ->label('Copyright')
                    ->maxLength(225),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = GeneralSetting::firstOrCreate(['id' => 1]);

        $oldLogo = $settings->logo;
        $data['hari_kerja'] = ! empty($data['hari_kerja']) ? implode(',', $data['hari_kerja']) : '1,2,3,4,5';

        $settings->update($data);

        if ($oldLogo && $oldLogo !== $data['logo'] && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }
}
