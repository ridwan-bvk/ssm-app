<?php

namespace App\Services;

use App\Support\MysqlBinaryLocator;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;
use Symfony\Component\Process\Process;

/**
 * Mirrors Admin\Backup from the CI4 app (DB backup/restore + uploads
 * backup/restore), but replaces the old app's raw shell string
 * interpolation (`passthru("mysqldump ... --password={$password}")`,
 * vulnerable to the password appearing in the process list and to shell
 * injection if any credential ever contained metacharacters) with
 * spatie/db-dumper for the dump (writes credentials to a temp file, not
 * the command line) and Symfony Process with array arguments + piped
 * stdin for the restore (per migration plan §5.6).
 */
class BackupService
{
    public function dumpDatabase(): string
    {
        $config = config('database.connections.'.config('database.default'));
        $filename = 'tmp/backup_db_'.now()->format('Ymd_His').'.sql';
        $path = Storage::disk('local')->path($filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $dumper = MySql::create()
            ->setHost($config['host'])
            ->setPort((int) $config['port'])
            ->setDbName($config['database'])
            ->setUserName($config['username'])
            ->setPassword($config['password'])
            ->setDumpBinaryPath(MysqlBinaryLocator::directory());

        // Not ->dumpToFile(): that builds and runs the Process internally
        // with no way to touch its environment. Doing it manually here lets
        // us merge in the env fix from MysqlBinaryLocator::processEnv() —
        // see its docblock for why that's necessary on Windows. Mirrors
        // dumpToFile()'s own body (credentials guard + temp file handle)
        // since getProcess() alone doesn't set those up.
        $dumper->guardAgainstIncompleteCredentials();
        $dumper->setTempFileHandle(tmpfile());

        $process = $dumper->getProcess($path);
        $process->setEnv(MysqlBinaryLocator::processEnv());
        $process->run();
        $dumper->checkIfDumpWasSuccessFul($process, $path);

        return $path;
    }

    public function restoreDatabase(string $uploadedSqlPath): void
    {
        $config = config('database.connections.'.config('database.default'));
        $mysqlBinary = MysqlBinaryLocator::binary('mysql');

        $process = new Process([
            $mysqlBinary,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--ssl-mode=DISABLED',
            $config['database'],
        ], env: MysqlBinaryLocator::processEnv());

        $process->setInput(fopen($uploadedSqlPath, 'r'));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Gagal merestore database: '.$process->getErrorOutput());
        }
    }

    public function zipUploads(): string
    {
        $filename = 'backup_photos_'.now()->format('Ymd_His').'.zip';
        $output = Storage::disk('local')->path('tmp/'.$filename);

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        $uploadsPath = Storage::disk('public')->path('');

        $zip = new \ZipArchive;
        $zip->open($output, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if (is_dir($uploadsPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsPath),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($files as $file) {
                if (! $file->isDir()) {
                    $relativePath = substr($file->getRealPath(), strlen($uploadsPath));
                    $zip->addFile($file->getRealPath(), $relativePath);
                }
            }
        }

        $zip->close();

        return $output;
    }

    public function restoreUploadsZip(string $uploadedZipPath): void
    {
        $uploadsPath = Storage::disk('public')->path('');

        $zip = new \ZipArchive;
        if ($zip->open($uploadedZipPath) !== true) {
            throw new \RuntimeException('Gagal membuka file zip.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $targetPath = $uploadsPath.$filename;

            if (file_exists($targetPath) && ! is_dir($targetPath)) {
                @unlink($targetPath);
            }
        }

        $zip->extractTo($uploadsPath);
        $zip->close();
    }
}
