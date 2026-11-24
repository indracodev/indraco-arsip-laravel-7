<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Services\ActivityLogger;
use App\Support\ArchiveDetail;
use App\Support\StatusLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Box label 10x10 cm / form A5 (Laravel 7 ArchiveLabelController + archives/print_sticker).
 * PIC Gudang and admin only (findings #14: Laravel 7 let PIC Departemen print without a button).
 * Printable statuses: approved_booked, in_warehouse, pending_extension. Opened as a page (Laravel 7
 * modal iframe `?embed=1` / `?json=1` not rebuilt).
 */
class ArchiveLabelController extends Controller
{
    public const PRINTABLE = ['approved_booked', 'in_warehouse', 'pending_extension'];

    private const RELATIONS = ['department', 'subDepartment', 'location.warehouse', 'rackSlot'];

    public function sticker(Request $request, Archive $archive): View|RedirectResponse
    {
        Gate::authorize('processWarehouse', Archive::class);
        if (! in_array($archive->status, self::PRINTABLE, true)) {
            return redirect()->route('archives.show', $archive)->with('error', 'Label box hanya dapat dicetak untuk arsip yang telah disetujui (Approved / Booked), tersimpan di gudang, atau dalam perpanjangan (status saat ini: '.StatusLabel::archive($archive->status)['label'].').');
        }
        ActivityLogger::log('ARCHIVE_PRINT', "Mencetak label / barcode box arsip '{$archive->box_number}' - {$archive->title}.", 'DOKUMEN_ARSIP',
            ['archive_id' => $archive->id, 'box_number' => $archive->box_number, 'title' => $archive->title], $archive->box_number);

        return $this->page(new Collection([$archive->load(self::RELATIONS)]), route('archives.show', $archive));
    }

    public function batch(Request $request): View|RedirectResponse
    {
        Gate::authorize('processWarehouse', Archive::class);
        $ids = $request->input('ids') ?: $request->input('archive_id');
        $ids = is_array($ids) ? $ids : array_filter(explode(',', (string) $ids));
        $ids = array_values(array_filter(array_map('intval', $ids)));

        $archives = Archive::query()->with(self::RELATIONS)->whereIn('status', self::PRINTABLE)
            ->when($ids !== [], fn (Builder $q) => $q->whereIn('id', $ids), fn (Builder $q) => $q
                ->when($request->filled('search'), fn (Builder $q) => $q->where(fn (Builder $q) => $q
                    ->where('title', 'like', '%'.$request->input('search').'%')->orWhere('box_number', 'like', '%'.$request->input('search').'%')
                    ->orWhere('periode_doc', 'like', '%'.$request->input('search').'%')->orWhere('period_text', 'like', '%'.$request->input('search').'%')))
                ->when($request->filled('department_id'), fn (Builder $q) => $q->where('department_id', $request->integer('department_id')))
                ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status'))))
            ->get();

        if ($archives->isEmpty()) {
            return redirect()->route('archives.index')->with('warning', 'Tidak ada data arsip yang memenuhi syarat untuk dicetak label.');
        }
        ActivityLogger::log('ARCHIVE_PRINT', 'Mencetak batch label box arsip sebanyak '.$archives->count().' berkas.', 'DOKUMEN_ARSIP',
            ['count' => $archives->count(), 'archive_ids' => $archives->pluck('id')->all()], "Batch: {$archives->count()} Box");

        return $this->page($archives, $archives->count() === 1 ? route('archives.show', $archives->first()) : route('archives.index'));
    }

    /** @param Collection<int, Archive> $archives */
    private function page(Collection $archives, string $backUrl): View
    {
        return view('archives.labels', ['labels' => $archives->map(fn (Archive $archive) => self::label($archive))->all(), 'backUrl' => $backUrl]);
    }

    /**
     * Values printed on one label: the @php block of Laravel 7 print_sticker.blade.php (rack letter
     * and 2-digit slot from the rack / slot, else derived from the box number or the id).
     *
     * @return array<string, mixed>
     */
    public static function label(Archive $archive): array
    {
        $years = (int) $archive->retention_years;
        $exp = match (true) {
            (bool) $archive->retention_expiry_date => $archive->retention_expiry_date->format('m-Y'),
            $archive->period_end_date && $years > 0 => $archive->period_end_date->copy()->addYears($years)->format('m-Y'),
            default => 'MM-YYYY',
        };
        $location = $archive->location;
        $slot = $archive->rackSlot;

        $letter = '';
        if ($location && $location->rack_code !== '') {
            $letter = preg_match('/([A-Za-z]+)$/', $location->rack_code, $m) ? strtoupper($m[1]) : '';
        } elseif ($slot && preg_match('/^([A-Za-z]+)/', (string) $slot->slot_code, $m)) {
            $letter = strtoupper($m[1]);
        }
        if ($letter === '') {
            $letter = $archive->box_number && preg_match('/([A-Za-z]{1,3})/', $archive->box_number, $m) ? strtoupper($m[1]) : chr(65 + intdiv($archive->id - 1, 20) % 26);
        }

        $number = null;
        if ($slot && $slot->slot_number) {
            $number = $slot->slot_number;
        } elseif ($location && preg_match('/(\d+)/', (string) $location->getAttribute('shelf_code'), $m)) {
            $number = (int) $m[1];
        }
        $number ??= $archive->box_number && preg_match('/(\d{1,3})$/', $archive->box_number, $m) ? ((int) $m[1] % 100 ?: 1) : ($archive->id - 1) % 20 + 1;
        $slotNo = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        return [
            'id' => $archive->id,
            'box_number' => $archive->box_number ?? 'DRAFT-BOX',
            'title' => $archive->title,
            'exp' => $exp,
            'letter' => $letter,
            'slot_no' => $slotNo,
            'code' => $letter.$slotNo,
            'allocated' => (bool) $archive->warehouse_location_id,
            'warehouse' => $location ? ($location->warehouse ? ($location->warehouse->name ?: $location->warehouse->code) : $location->room_sector) : null,
            'rack' => $location?->rack_code,
            'slot' => $slot ? "Sap {$slot->getAttribute('sap_level')}, ".($slot->getAttribute('layer') === 'top' ? 'Baris Atas' : 'Baris Bawah')." Slot {$slot->slot_number}" : null,
            'department' => ($archive->department->name ?? 'Departemen').($archive->subDepartment ? ' / '.$archive->subDepartment->name : ''),
            'handover' => ($archive->tgl_penyerahan ?? Carbon::today())->format('d/m/Y'),
            'period' => $archive->effectivePeriode(),
            'retention' => ArchiveDetail::retentionLabel($archive),
            'contents' => $archive->content_description ?? $archive->title,
        ];
    }
}
