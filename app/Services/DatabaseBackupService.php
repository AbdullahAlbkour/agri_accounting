<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * خدمة توليد نسخة احتياطية من قاعدة البيانات بصيغة ملف SQL.
 *
 * تعتمد أولاً على أداة mysqldump إن كانت متوفرة على الخادم (أسرع وأدق)،
 * وإن لم تتوفر تُولّد النسخة عبر PHP مباشرة من الاتصال الحالي بقاعدة البيانات.
 */
class DatabaseBackupService
{
    /**
     * اسم الملف المقترح للنسخة الاحتياطية.
     */
    public function fileName(): string
    {
        $database = $this->databaseName() ?: 'database';
        $database = preg_replace('/[^A-Za-z0-9_\-]/', '_', $database);

        return 'agri-backup-'.$database.'-'.date('Y-m-d_His').'.sql';
    }

    public function driver(): string
    {
        return (string) DB::connection()->getDriverName();
    }

    public function databaseName(): ?string
    {
        return DB::connection()->getDatabaseName();
    }

    /**
     * هل أداة mysqldump متاحة على الخادم؟
     */
    public function mysqldumpAvailable(): bool
    {
        if ($this->driver() !== 'mysql' || ! function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (in_array('exec', $disabled, true)) {
            return false;
        }

        @exec('mysqldump --version 2>/dev/null', $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * توليد محتوى النسخة الاحتياطية كاملاً كنص SQL.
     */
    public function generate(): string
    {
        if ($this->mysqldumpAvailable()) {
            $dump = $this->generateWithMysqldump();

            if ($dump !== null && trim($dump) !== '') {
                return $dump;
            }
        }

        return $this->generateWithPhp();
    }

    /**
     * النسخ الاحتياطي عبر أداة mysqldump.
     */
    protected function generateWithMysqldump(): ?string
    {
        $config = DB::connection()->getConfig();

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --events --add-drop-table --default-character-set=utf8mb4 %s 2>/dev/null',
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['database'] ?? '')
        );

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return implode(PHP_EOL, $output).PHP_EOL;
    }

    /**
     * النسخ الاحتياطي عبر PHP مباشرة (يعمل على MySQL و SQLite).
     */
    protected function generateWithPhp(): string
    {
        $driver = $this->driver();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new RuntimeException('نوع قاعدة البيانات الحالي ('.$driver.') غير مدعوم في النسخ الاحتياطي المباشر.');
        }

        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);

        $sql = '-- نسخة احتياطية من نظام المحاسبة الزراعية'.PHP_EOL;
        $sql .= '-- قاعدة البيانات: '.$this->databaseName().PHP_EOL;
        $sql .= '-- تاريخ الإنشاء: '.date('Y-m-d H:i:s').PHP_EOL;
        $sql .= '-- المحرك: '.$driver.PHP_EOL.PHP_EOL;

        if ($isMysql) {
            $sql .= 'SET NAMES utf8mb4;'.PHP_EOL;
            $sql .= 'SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL.PHP_EOL;
        } else {
            $sql .= 'PRAGMA foreign_keys=OFF;'.PHP_EOL;
            $sql .= 'BEGIN TRANSACTION;'.PHP_EOL.PHP_EOL;
        }

        foreach ($this->tables() as $table => $createStatement) {
            $quoted = $this->quoteIdentifier($table);

            $sql .= '-- ----------------------------'.PHP_EOL;
            $sql .= '-- بنية الجدول: '.$table.PHP_EOL;
            $sql .= '-- ----------------------------'.PHP_EOL;
            $sql .= 'DROP TABLE IF EXISTS '.$quoted.';'.PHP_EOL;
            $sql .= rtrim(trim($createStatement), ';').';'.PHP_EOL.PHP_EOL;

            $rows = DB::connection()->table($table)->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $sql .= '-- بيانات الجدول: '.$table.PHP_EOL;

            foreach ($rows as $row) {
                $row = (array) $row;
                $columns = implode(', ', array_map(fn ($c) => $this->quoteIdentifier($c), array_keys($row)));
                $values = implode(', ', array_map(fn ($v) => $this->quoteValue($v), array_values($row)));

                $sql .= 'INSERT INTO '.$quoted.' ('.$columns.') VALUES ('.$values.');'.PHP_EOL;
            }

            $sql .= PHP_EOL;
        }

        $sql .= $isMysql
            ? 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL
            : 'COMMIT;'.PHP_EOL.'PRAGMA foreign_keys=ON;'.PHP_EOL;

        return $sql;
    }

    /**
     * قائمة الجداول مع جملة الإنشاء الخاصة بكل جدول.
     *
     * @return array<string, string>
     */
    protected function tables(): array
    {
        $connection = DB::connection();
        $tables = [];

        if ($this->driver() === 'sqlite') {
            $rows = $connection->select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            foreach ($rows as $row) {
                $tables[$row->name] = $row->sql;
            }

            return $tables;
        }

        foreach ($connection->select('SHOW TABLES') as $row) {
            $name = array_values((array) $row)[0];
            $create = (array) $connection->select('SHOW CREATE TABLE '.$this->quoteIdentifier($name))[0];
            $tables[$name] = array_values($create)[1] ?? '';
        }

        return $tables;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        if ($this->driver() === 'sqlite') {
            return '"'.str_replace('"', '""', $identifier).'"';
        }

        return '`'.str_replace('`', '``', $identifier).'`';
    }

    protected function quoteValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}
