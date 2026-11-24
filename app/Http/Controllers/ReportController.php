<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Warehouse;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Pusat Laporan & Dokumen PDF" (Laravel 7 ReportController), admin only (finding #6): page with
 * the 7 report tabs, printable A4 landscape page (B8: no missing css/app.css) and CSV export
 * (streamed row by row, formula-safe cells). The page filters on the server (?q=) instead of in JS.
 */
class ReportController extends Controller
{
    public function index(Request $request, ReportBuilder $builder): View
    {
        $type = $request->query('type', 'master_departments');
        $type = is_string($type) && isset(ReportBuilder::TYPES[$type]) ? $type : 'master_departments';

        return view('reports.index', $this->report($type, $request, $builder) + [
            'departments' => Department::query()->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function print(Request $request, string $type, ReportBuilder $builder): View
    {
        abort_unless(isset(ReportBuilder::TYPES[$type]), 404, 'Tipe laporan tidak ditemukan');

        return view('reports.print', $this->report($type, $request, $builder) + [
            'autoPrint' => $request->boolean('auto_print'),
            'printedAt' => Carbon::now()->translatedFormat('j F Y, H:i').' WIB',
        ]);
    }

    public function exportCsv(Request $request, string $type, ReportBuilder $builder): StreamedResponse
    {
        abort_unless(isset(ReportBuilder::TYPES[$type]), 404, 'Tipe laporan tidak ditemukan');
        [, $title, , $columns] = ReportBuilder::TYPES[$type];
        $rows = $builder->rows($type, $this->filters($request));
        $user = $request->user()->name ?? 'System Administrator';

        return response()->streamDownload(function () use ($title, $columns, $rows, $user): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            $put = fn (array $cells) => fputcsv($out, array_map([ReportBuilder::class, 'csvCell'], $cells), ',', '"', '');
            $put(['PT INDRACO - DOCUMENT MANAGEMENT SYSTEM']);
            $put([mb_strtoupper($title)]);
            $put(['Dicetak Pada:', Carbon::now()->translatedFormat('j F Y, H:i').' WIB']);
            $put(['Dicetak Oleh:', $user]);
            $put([]);
            $put($columns);
            foreach ($rows as $row) {
                $put($row);
            }
            fclose($out);
        }, 'Laporan_'.str_replace(' ', '_', $title).'_'.date('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /** @return array<string, mixed> */
    private function report(string $type, Request $request, ReportBuilder $builder): array
    {
        [, $title, $subtitle, $columns] = ReportBuilder::TYPES[$type];
        $generator = $builder->rows($type, $this->filters($request));
        $rows = iterator_to_array($generator, false);

        return ['type' => $type, 'title' => $title, 'subtitle' => $subtitle, 'columns' => $columns, 'rows' => $rows, 'summary' => $generator->getReturn()];
    }

    /** @return array{department_id: string|null, warehouse_id: string|null, q: string|null} */
    private function filters(Request $request): array
    {
        $valid = $request->validate([
            'department_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return ['department_id' => $valid['department_id'] ?? null, 'warehouse_id' => $valid['warehouse_id'] ?? null, 'q' => $valid['q'] ?? null];
    }
}
