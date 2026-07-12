<?php

namespace App\Support;

/**
 * Resolves the mysql/mysqldump binaries, honoring the optional
 * MYSQL_BIN_PATH env var for environments where they aren't on PATH
 * (e.g. Laragon/XAMPP on Windows). Shared by BackupService and the
 * absensi:import-legacy command so both use one source of truth.
 */
class MysqlBinaryLocator
{
    public static function directory(): string
    {
        $path = config('database.mysql_bin_path', '');

        return $path ? rtrim($path, '/\\').DIRECTORY_SEPARATOR : '';
    }

    public static function binary(string $name): string
    {
        return static::directory().$name;
    }

    /**
     * Extra environment variables to merge into a spawned mysql/mysqldump
     * process on top of whatever the current PHP process already inherited.
     *
     * On Windows, a child process that boots without SystemRoot/windir set
     * fails to initialize Winsock and mysqldump dies with "Got error: 2004:
     * Can't create TCP/IP socket (10106)" — even though the exact same
     * binary works fine when run directly from a terminal. Symfony Process
     * normally inherits the parent PHP process's environment, but that
     * inherited environment is only as complete as whatever launched PHP
     * itself (e.g. some terminal/service wrappers strip it down), so this
     * explicitly guarantees the variables Winsock needs are present
     * regardless of how the app server was started.
     */
    public static function processEnv(): array
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return [];
        }

        return array_filter([
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'windir' => getenv('windir') ?: getenv('SystemRoot') ?: 'C:\\Windows',
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
        ]);
    }
}
