<?php

namespace App\Filament\Clusters\Laporan\Pages;

use App\Filament\Clusters\Laporan;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\GeneratesMonthlyPdfReport;
use App\Filament\Concerns\HasKelasOptions;
use App\Services\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mirrors Admin\GenerateLaporan::generateLaporanSiswa() from the CI4 app,
 * replacing the old "window.print() on HTML" / "HTML served with a .doc
 * mime type" hacks with a real PDF render via dompdf.
 */
class LaporanSiswa extends Page implements HasForms
{
    use AuthorizesViaPermission;
    use GeneratesMonthlyPdfReport;
    use HasKelasOptions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static ?string $cluster = Laporan::class;

    protected static string $view = 'filament.pages.laporan-siswa';

    protected static ?string $permission = 'attendance.view';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['bulan' => now()->toDateString()]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Select::make('id_kelas')
                    ->label('Kelas')
                    ->options(fn () => static::kelasOptions())
                    ->required(),
                DatePicker::make('bulan')->label('Bulan')->displayFormat('F Y')->required(),
            ]);
    }

    public function download()
    {
        $data = $this->form->getState();
        $report = app(ReportService::class)->monthlySiswa((int) $data['id_kelas'], $data['bulan']);
        $filename = 'laporan_absen_'.Str::slug($report['kelas']->tingkat.' '.$report['kelas']->index_kelas).'_'.Carbon::parse($data['bulan'])->format('F-Y').'.pdf';

        return static::downloadMonthlyReportPdf($report, 'siswa', 'Data siswa kosong', 'reports.siswa', $filename);
    }
}
