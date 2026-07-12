<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mirrors Admin\QRGenerator from the CI4 app: same brand colors (blue for
 * students, green for teachers), 300px/margin-10/high-error-correction QR
 * settings, payload = unique_code. Files are written to the `public` disk
 * instead of a raw FCPATH path for portability.
 */
class QrService
{
    private const SISWA_COLOR = [44, 73, 162];

    private const GURU_COLOR = [28, 101, 90];

    public function generateForSiswa(Siswa $siswa): string
    {
        $slug = $this->kelasSlug($siswa->kelas);
        $filename = Str::slug($siswa->nama_siswa).'_'.Str::slug($siswa->nis).'.png';
        $path = "qr-siswa/{$slug}/{$filename}";

        $this->write($siswa->unique_code, $siswa->nama_siswa, self::SISWA_COLOR, $path);

        return $path;
    }

    public function generateForGuru(Guru $guru): string
    {
        $filename = Str::slug($guru->nama_guru).'_'.Str::slug($guru->nuptk).'.png';
        $path = "qr-guru/{$filename}";

        $this->write($guru->unique_code, $guru->nama_guru, self::GURU_COLOR, $path);

        return $path;
    }

    public function kelasSlug(?Kelas $kelas): string
    {
        if (! $kelas) {
            return 'tmp';
        }

        return Str::slug("{$kelas->tingkat} {$kelas->jurusan?->jurusan} {$kelas->index_kelas}");
    }

    private function write(string $uniqueCode, string $label, array $color, string $relativePath): void
    {
        [$r, $g, $b] = $color;

        $qrCode = QrCode::create($uniqueCode)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(300)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color($r, $g, $b))
            ->setBackgroundColor(new Color(255, 255, 255));

        $fontPath = public_path('fonts/Roboto-Medium.ttf');
        $label = Label::create($label)->setTextColor(new Color($r, $g, $b));

        if (is_file($fontPath)) {
            $label = $label->setFont(new Font($fontPath, 14));
        }

        $result = (new PngWriter)->write($qrCode, null, $label);

        Storage::disk('public')->put($relativePath, $result->getString());
    }

    /**
     * @return string absolute zip file path on the local disk
     */
    public function zipFolder(string $relativeFolder, string $zipFilename): string
    {
        $absoluteFolder = Storage::disk('public')->path($relativeFolder);
        $output = Storage::disk('local')->path('tmp/'.$zipFilename);

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        $zip = new \ZipArchive;
        $zip->open($output, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if (is_dir($absoluteFolder)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluteFolder),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($files as $file) {
                if (! $file->isDir()) {
                    $relativePath = substr($file->getRealPath(), strlen($absoluteFolder) + 1);
                    $zip->addFile($file->getRealPath(), $relativePath);
                }
            }
        }

        $zip->close();

        return $output;
    }
}
