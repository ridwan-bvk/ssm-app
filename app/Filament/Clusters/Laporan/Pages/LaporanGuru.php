<?php

namespace App\Filament\Clusters\Laporan\Pages;

use App\Filament\Clusters\Laporan;
use App\Filament\Concerns\AuthorizesViaPermission;
use App\Filament\Concerns\GeneratesMonthlyPdfReport;
use App\Services\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Mirrors Admin\GenerateLaporan::generateLaporanGuru() from the CI4 app.
 */
class LaporanGuru extends Page implements HasForms
{
    use AuthorizesViaPermission;
    use GeneratesMonthlyPdfReport;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Guru';

    protected static ?string $cluster = Laporan::class;

    protected static string $view = 'filament.pages.laporan-guru';

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
                DatePicker::make('bulan')->label('Bulan')->displayFormat('F Y')->required(),
            ]);
    }

    public function download()
    {
        $data = $this->form->getState();
        $report = app(ReportService::class)->monthlyGuru($data['bulan']);
        $filename = 'laporan_absen_guru_'.Carbon::parse($data['bulan'])->format('F-Y').'.pdf';

        return static::downloadMonthlyReportPdf($report, 'guru', 'Data guru kosong', 'reports.guru', $filename);
    }
}
