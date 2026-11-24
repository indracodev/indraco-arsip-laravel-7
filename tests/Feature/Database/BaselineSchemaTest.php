<?php

namespace Tests\Feature\Database;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

/**
 * The baseline migration must reproduce the Laravel 7 schema exactly, and must leave an
 * existing Laravel 7 database untouched. Reference: tests/Fixtures/l7_schema.json
 * (sqlite_master of the production database).
 */
class BaselineSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** Indexes added after the baseline (R4); everything else is the Laravel 7 schema. */
    private const R4_INDEXES = [
        ['index', 'app_settings_key_unique', 'app_settings', 'CREATE UNIQUE INDEX "app_settings_key_unique" ON "app_settings" ("key")'],
        ['index', 'users_email_unique', 'users', 'CREATE UNIQUE INDEX "users_email_unique" ON "users" (lower(trim("email")))'],
    ];

    private ?string $legacyPath = null;

    protected function tearDown(): void
    {
        if ($this->legacyPath !== null) {
            DB::purge('legacy');
            foreach (['', '-wal', '-shm'] as $suffix) {
                @unlink($this->legacyPath.$suffix);
            }
        }

        parent::tearDown();
    }

    public function test_fresh_migration_matches_the_laravel7_schema(): void
    {
        $actual = $this->normalizedSchema(DB::connection()->getPdo());

        $this->assertCount(50, $actual, '17 tables + 31 Laravel 7 indexes + 2 R4 indexes');
        $this->assertSame($this->withR4($this->expectedSchema()), $actual);
    }

    public function test_migrate_on_an_existing_laravel7_database_changes_nothing(): void
    {
        $pdo = $this->legacyDatabase();
        $pdo->exec("INSERT INTO departments (code, name, retention_years, is_active) VALUES ('FIN', 'FIN', 10, 1)");
        $pdo->exec("INSERT INTO users (name, email, password, role, department_id) VALUES ('PIC', 'fin@indraco.com', 'x', 'pic_dept', 1)");
        $pdo->exec("INSERT INTO archives (department_id, created_by_user_id, title, period_start_date, period_end_date, content_description, status, box_number) VALUES (1, 1, 'Box', '2026-01-01', '2026-12-31', 'isi', 'in_warehouse', 'IND/FIN/2026/0001')");
        $schemaBefore = $this->normalizedSchema($pdo);
        $rowsBefore = $this->tableDump($pdo);

        $exitCode = Artisan::call('migrate', ['--database' => 'legacy', '--force' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame($this->withR4($schemaBefore), $this->normalizedSchema($pdo));
        $migrations = $pdo->query('SELECT migration FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([...$this->fixture()['l7_migrations'], '0001_01_01_000000_baseline_dms_schema', '2026_11_24_000001_add_unique_email_and_setting_key'], $migrations);
        unset($rowsBefore['migrations']);
        $rowsAfter = $this->tableDump($pdo);
        unset($rowsAfter['migrations']);
        $this->assertSame($rowsBefore, $rowsAfter);
    }

    public function test_migrate_refuses_a_database_with_only_part_of_the_schema(): void
    {
        $pdo = $this->legacyDatabase(onlyTable: 'users');

        $exitCode = rescue(fn () => Artisan::call('migrate', ['--database' => 'legacy', '--force' => true]), 1, false);

        $this->assertNotSame(0, $exitCode);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['migrations', 'users'], $tables);
    }

    public function test_unique_email_migration_stops_on_duplicates_and_changes_nothing(): void
    {
        $pdo = $this->legacyDatabase();
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('A', 'pic@indraco.com', 'x', 'admin'), ('B', ' PIC@Indraco.com', 'x', 'admin')");
        $schemaBefore = $this->normalizedSchema($pdo);

        $exitCode = rescue(fn () => Artisan::call('migrate', ['--database' => 'legacy', '--force' => true]), 1, false);

        $this->assertNotSame(0, $exitCode);
        $this->assertSame($schemaBefore, $this->normalizedSchema($pdo));
        $this->assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
    }

    public function test_unique_email_index_rejects_the_same_address_in_another_case(): void
    {
        DB::table('users')->insert(['name' => 'A', 'email' => 'pic@indraco.com', 'password' => 'x', 'role' => 'admin']);

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('users')->insert(['name' => 'B', 'email' => 'PIC@indraco.com ', 'password' => 'x', 'role' => 'admin']);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $schema
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function withR4(array $schema): array
    {
        $schema = [...$schema, ...self::R4_INDEXES];
        usort($schema, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return $schema;
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function normalizedSchema(PDO $pdo): array
    {
        $rows = $pdo->query("SELECT type, name, tbl_name, sql FROM sqlite_master WHERE name NOT LIKE 'sqlite_%' AND name <> 'migrations' ORDER BY type, name")->fetchAll(PDO::FETCH_NUM);

        return array_map(fn (array $r): array => [$r[0], $r[1], $r[2], $this->squash($r[3])], $rows);
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function expectedSchema(): array
    {
        $objects = array_filter($this->fixture()['objects'], fn (array $o): bool => $o['name'] !== 'migrations');

        return array_values(array_map(fn (array $o): array => [$o['type'], $o['name'], $o['tbl_name'], $this->squash($o['sql'])], $objects));
    }

    private function squash(string $sql): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $sql));
    }

    /**
     * @return array{objects: list<array{type: string, name: string, tbl_name: string, sql: string}>, l7_migrations: list<string>}
     */
    private function fixture(): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/l7_schema.json')), true);
    }

    /**
     * A temporary file database shaped like the Laravel 7 one (schema + its migrations rows),
     * registered as connection "legacy".
     */
    private function legacyDatabase(?string $onlyTable = null): PDO
    {
        $this->legacyPath = tempnam(sys_get_temp_dir(), 'dms-l7-');
        config(['database.connections.legacy' => ['database' => $this->legacyPath] + config('database.connections.sqlite')]);
        $pdo = DB::connection('legacy')->getPdo();

        $objects = $this->fixture()['objects'];
        usort($objects, fn (array $a, array $b): int => strcmp($b['type'], $a['type'])); // tables before indexes
        foreach ($objects as $object) {
            if ($onlyTable === null || in_array($object['name'], [$onlyTable, 'migrations'], true)) {
                $pdo->exec($object['sql']);
            }
        }

        $insert = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, 1)');
        foreach ($this->fixture()['l7_migrations'] as $migration) {
            $insert->execute([$migration]);
        }

        return $pdo;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function tableDump(PDO $pdo): array
    {
        $dump = [];
        foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $dump[$table] = $pdo->query("SELECT * FROM \"{$table}\" ORDER BY rowid")->fetchAll(PDO::FETCH_ASSOC);
        }

        return $dump;
    }
}
