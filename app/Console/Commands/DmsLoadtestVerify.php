<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Checks the data written by tests/k6/write_flow.js (titles "LOADTEST-<run id>-..."): counts per
 * status, box numbers unique and gap-free (one global counter: every counter between the lowest
 * and highest test box must be held by some archive), no orphaned rows. Exit 1 on a problem.
 * Without a run id every LOADTEST run is checked together (two tester PCs).
 */
#[Signature('dms:loadtest-verify {run? : Run id (kosong = semua run LOADTEST)}')]
#[Description('Verifikasi hasil uji beban tulis: nomor box unik tanpa lompat, tanpa baris yatim, jumlah per status')]
class DmsLoadtestVerify extends Command
{
    public function handle(): int
    {
        $like = 'LOADTEST-'.($this->argument('run') ? $this->argument('run').'-' : '').'%';
        $ids = DB::table('archives')->where('title', 'like', $like)->pluck('id')->all();
        $this->components->info("Arsip {$like}: ".count($ids));
        $this->counts('archives', 'status', 'id', $ids);
        $this->counts('borrowing_logs', 'status', 'archive_id', $ids);
        $this->counts('archive_extension_logs', 'approval_status', 'archive_id', $ids);
        $this->counts('destruction_logs', 'approval_status', 'archive_id', $ids);
        $this->components->twoColumnDetail('archive_items', (string) DB::table('archive_items')->whereIn('archive_id', $ids)->count());

        $problems = array_merge($this->boxNumberProblems($like), $this->orphans());
        foreach ($problems as $problem) {
            $this->components->error($problem);
        }
        if ($problems === []) {
            $this->components->info('OK: nomor box unik tanpa lompatan, tidak ada baris yatim.');
        }

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @param list<int> $ids */
    private function counts(string $table, string $column, string $key, array $ids): void
    {
        $rows = DB::table($table)->whereIn($key, $ids)->selectRaw("{$column} AS s, COUNT(*) AS n")->groupBy($column)->pluck('n', 's');
        $this->components->twoColumnDetail($table, $rows->isEmpty() ? '0' : $rows->map(fn ($n, $s) => "{$s}: {$n}")->implode(', '));
    }

    /** @return list<string> */
    private function boxNumberProblems(string $like): array
    {
        $problems = [];
        $test = DB::table('archives')->where('title', 'like', $like)->whereNotNull('box_number')->pluck('box_number');
        foreach ($test->countBy()->filter(fn (int $n) => $n > 1) as $box => $n) {
            $problems[] = "Nomor box ganda: {$box} ({$n} arsip)";
        }
        $counter = fn (string $box): ?int => preg_match('/(\d+)$/', $box, $m) ? (int) $m[1] : null;
        $testCounters = $test->map($counter)->filter()->values();
        foreach ($testCounters->countBy()->filter(fn (int $n) => $n > 1) as $c => $n) {
            $problems[] = "Counter box {$c} dipakai {$n} box uji (counter harus unik)";
        }
        if ($testCounters->isNotEmpty()) {
            $held = DB::table('archives')->whereNotNull('box_number')->pluck('box_number')->map($counter)->filter()->flip();
            $gaps = array_values(array_filter(range($testCounters->min(), $testCounters->max()), fn (int $c): bool => ! $held->has($c)));
            if ($gaps !== []) {
                $problems[] = 'Counter box terlewat (lompat): '.implode(', ', array_slice($gaps, 0, 30)).(count($gaps) > 30 ? ' ...' : '');
            }
            $this->components->twoColumnDetail('Counter box uji', $testCounters->min().' - '.$testCounters->max().' ('.$testCounters->count().' box)');
        }

        return $problems;
    }

    /** @return list<string> */
    private function orphans(): array
    {
        $checks = [
            'archive_items tanpa arsip' => 'SELECT COUNT(*) FROM archive_items i WHERE NOT EXISTS (SELECT 1 FROM archives a WHERE a.id = i.archive_id)',
            'borrowing_logs tanpa arsip' => 'SELECT COUNT(*) FROM borrowing_logs l WHERE NOT EXISTS (SELECT 1 FROM archives a WHERE a.id = l.archive_id)',
            'destruction_logs tanpa arsip' => 'SELECT COUNT(*) FROM destruction_logs l WHERE NOT EXISTS (SELECT 1 FROM archives a WHERE a.id = l.archive_id)',
            'archive_extension_logs tanpa arsip' => 'SELECT COUNT(*) FROM archive_extension_logs l WHERE NOT EXISTS (SELECT 1 FROM archives a WHERE a.id = l.archive_id)',
            'slot rak menunjuk arsip yang tidak ada' => 'SELECT COUNT(*) FROM warehouse_rack_slots s WHERE s.archive_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM archives a WHERE a.id = s.archive_id)',
            'arsip uji memegang lebih dari 1 slot rak' => "SELECT COUNT(*) FROM (SELECT s.archive_id FROM warehouse_rack_slots s JOIN archives a ON a.id = s.archive_id WHERE a.title LIKE 'LOADTEST-%' GROUP BY s.archive_id HAVING COUNT(*) > 1)",
            'slot rak arsip uji tidak sama dengan archives.warehouse_rack_slot_id' => "SELECT COUNT(*) FROM warehouse_rack_slots s JOIN archives a ON a.id = s.archive_id WHERE a.title LIKE 'LOADTEST-%' AND a.warehouse_rack_slot_id <> s.id",
            'arsip uji di rak bergrid tanpa slot' => "SELECT COUNT(*) FROM archives a WHERE a.title LIKE 'LOADTEST-%' AND a.status = 'in_warehouse' AND a.warehouse_rack_slot_id IS NULL AND EXISTS (SELECT 1 FROM warehouse_rack_slots s WHERE s.warehouse_location_id = a.warehouse_location_id)",
            'arsip uji dimusnahkan/dikeluarkan tapi masih memegang slot' => "SELECT COUNT(*) FROM warehouse_rack_slots s JOIN archives a ON a.id = s.archive_id WHERE a.title LIKE 'LOADTEST-%' AND a.status IN ('destroyed', 'taken')",
        ];
        $problems = [];
        foreach ($checks as $label => $sql) {
            $n = (int) (array_values((array) DB::selectOne($sql))[0] ?? 0);
            if ($n > 0) {
                $problems[] = "{$label}: {$n}";
            }
        }

        return $problems;
    }
}
