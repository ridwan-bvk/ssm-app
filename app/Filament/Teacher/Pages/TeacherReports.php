<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Concerns\AuthorizesViaRole;
use App\Filament\Concerns\GeneratesMonthlyPdfReport;
use App\Services\ReportService;
use App\Services\WaliKelasResolver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mirrors Teacher\Reports from the CI4 app: a monthly PDF report scoped to
 * the teacher's own class (server-derived, never a client-supplied
 * id_kelas — unlike the old app's version, which was functionally
 * duplicated from Admin\GenerateLaporan but hardcoded to the teacher's
 * class, now unified into the same ReportService both panels use).
 */
class TeacherReports extends Page implements HasForms
{
    use AuthorizesViaRole;
    use GeneratesMonthlyPdfReport;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Kelas';

    protected static string $view = 'filament.teacher.pages.teacher-reports';

    protected static ?string $requiredRole = 'guru';

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
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        if (! $kelas) {
            Notification::make()->title('Anda belum ditugaskan sebagai wali kelas')->danger()->send();

            return null;
        }

        $data = $this->form->getState();
        $report = app(ReportService::class)->monthlySiswa($kelas->id_kelas, $data['bulan']);
        $filename = 'laporan_absen_'.Str::slug($kelas->tingkat.' '.$kelas->index_kelas).'_'.Carbon::parse($data['bulan'])->format('F-Y').'.pdf';

        return static::downloadMonthlyReportPdf($report, 'siswa', 'Data siswa kosong', 'reports.siswa', $filename);
    }
}
