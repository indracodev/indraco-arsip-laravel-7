<?php

namespace App\Services;

use App\Actions\Archive\SaveArchiveDraft;
use App\Actions\Archive\SubmitArchiveBoxes;
use App\Actions\Warehouse\CheckinArchive;
use App\Http\Requests\ArchiveSubmissionRequest;
use App\Models\Archive;
use App\Models\Department;
use App\Models\User;
use App\Models\WarehouseLocation;
use App\Models\WarehouseRackSlot;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Multi-box Excel import (Laravel 7 ArchiveImportController + MultiBoxExcelService): preview,
 * commit and "Lanjutkan ke Form Pengajuan". The parsed boxes are checked with the submission
 * form's own rules (ArchiveSubmissionRequest) and saved by the form's own Actions
 * (SaveArchiveDraft / SubmitArchiveBoxes), so an imported box is exactly what the form would save.
 *
 * The file is parsed again on commit / load-to-form (never the client's preview JSON, which
 * Laravel 7 trusted, department included).
 */
final class ArchiveImport
{
    /** Column names of the template in validation messages. */
    private const ATTRIBUTES = [
        'boxes.*.title' => 'JUDUL_BOX', 'boxes.*.sub_department_id' => 'KODE_SUB_DEPARTEMEN', 'boxes.*.document_type' => 'KATEGORI_DOKUMEN',
        'boxes.*.periode' => 'MASA_SIMPAN_BULAN', 'boxes.*.physical_condition' => 'KONDISI_FISIK',
        'boxes.*.items.*.document_name' => 'NAMA_DOKUMEN', 'boxes.*.items.*.period_start' => 'PERIODE_DOK_MULAI',
        'boxes.*.items.*.period_end' => 'PERIODE_DOK_SELESAI', 'boxes.*.items.*.notes' => 'KETERANGAN_DOKUMEN',
    ];

    public function __construct(
        private ArchiveImportParser $parser,
        private ArchiveBoxBuilder $builder,
        private SaveArchiveDraft $draft,
        private SubmitArchiveBoxes $submit,
        private CheckinArchive $checkin,
    ) {}

    /**
     * Laravel 7 preview JSON (`success, errors, warnings, boxes, total_boxes, total_items`).
     * Errors block every import; what only blocks "Verifikasi Gudang" is a warning.
     *
     * @return array<string, mixed>
     */
    public function preview(UploadedFile $file, User $user): array
    {
        $parsed = $this->parser->parse($file, $user);
        $errors = $parsed['errors'];
        $warnings = $parsed['warnings'];
        foreach ($this->groups($parsed['boxes']) as $group) {
            $errors = [...$errors, ...array_values($this->check($user, $group, true, null)[0])];
            // No scan in the preview: the missing scan form is reported on commit only.
            foreach (array_diff_key($this->check($user, $group, false, null)[0], ['scan_input_form' => 0]) as $message) {
                $warnings[] = "Belum lengkap untuk Verifikasi Gudang: {$message}";
            }
        }
        $boxes = array_map($this->previewBox(...), $parsed['boxes']);

        return ['success' => $errors === [], 'errors' => $errors, 'warnings' => $warnings, 'boxes' => $boxes,
            'total_boxes' => count($boxes), 'total_items' => array_sum(array_map(fn (array $b): int => count($b['items']), $boxes))];
    }

    /**
     * Save as `draft` (one draft row per department, like the form) or `pending_verification`
     * (one row per box; the scan form is required, like the form). All or nothing.
     *
     * @return array{errors: list<string>, message: string}
     */
    public function commit(UploadedFile $file, User $user, string $status, ?UploadedFile $scan): array
    {
        if ($status === 'in_warehouse' && ! $user->isAdmin()) {
            return ['errors' => ['Hanya Admin yang dapat mengimpor langsung ke gudang.'], 'message' => ''];
        }
        $isDraft = $status === 'draft';
        $isWarehouse = $status === 'in_warehouse';
        $parsed = $this->parser->parse($file, $user);
        $groups = $this->groups($parsed['boxes']);
        $errors = $parsed['errors'];
        foreach ($groups as $key => $group) {
            [$messages, $groups[$key]['data']] = $this->check($user, $group, $isDraft, $isWarehouse ? null : $scan, $isWarehouse);
            $errors = [...$errors, ...array_values($messages)];
        }
        if ($errors !== [] || $groups === []) {
            return ['errors' => $errors ?: ['File tidak berisi box arsip yang dapat diimpor.'], 'message' => ''];
        }

        $uploads = ['scan' => ($isDraft || $isWarehouse) ? null : $scan, 'file' => null, 'remove_scan' => false, 'remove_file' => false];
        try {
            // ponytail: one transaction over the per-department Actions; if a later department fails, a scan
            // already stored for an earlier one stays on disk unreferenced (admin multi-department files only).
            DB::transaction(function () use ($groups, $user, $uploads, $isDraft, $isWarehouse): void {
                foreach ($groups as $group) {
                    if ($isDraft) {
                        $this->draft->handle($user, $group['department'], $group['data'], $uploads);
                    } elseif ($isWarehouse) {
                        $saved = $this->submit->createAndReturn($user, $group['department'], $group['data'], $uploads);
                        $this->checkinImportedBoxes($user, $saved, $group['boxes']);
                    } else {
                        $this->submit->handle($user, $group['department'], $group['data'], $uploads);
                    }
                }
            });
        } catch (DomainException $e) {
            return ['errors' => [$e->getMessage()], 'message' => ''];
        }

        $boxCount = count($parsed['boxes']);
        $itemCount = array_sum(array_map(fn (array $b): int => count($b['items']), $parsed['boxes']));
        $statusLabel = match ($status) {
            'draft' => 'Draft Sementara',
            'in_warehouse' => 'Tersimpan di Gudang',
            default => 'Antrean Verifikasi Gudang',
        };
        ActivityLogger::log('ARCHIVE_BATCH_IMPORT', "Mengimpor massal {$itemCount} butir dokumen ke dalam {$boxCount} box arsip (Status: {$statusLabel}) oleh {$user->name}.",
            'IMPORT_EXCEL', ['boxes_count' => $boxCount, 'items_count' => $itemCount, 'status' => $status], "{$boxCount} Box Arsip", $user);

        return ['errors' => [], 'message' => "Berhasil mengimpor {$boxCount} box arsip dengan {$itemCount} butir dokumen."];
    }

    /**
     * Old input for the create form (Laravel 7 loadImportToCreateForm put it in the session).
     *
     * @return array{errors: list<string>, input: array<string, mixed>, boxes: int, items: int}
     */
    public function formInput(UploadedFile $file, User $user): array
    {
        $parsed = $this->parser->parse($file, $user);
        $boxes = $parsed['boxes'];
        $input = ['department_id' => $boxes[0]['department']->id ?? $user->department_id, 'tgl_penyerahan' => now()->format('Y-m-d'), 'boxes' => $this->formBoxes($boxes)];

        return ['errors' => $parsed['errors'] ?: ($boxes === [] ? ['File tidak berisi box arsip yang dapat diimpor.'] : []), 'input' => $input,
            'boxes' => count($boxes), 'items' => array_sum(array_map(fn (array $b): int => count($b['items']), $boxes))];
    }

    /**
     * Run the form's validation on one department's boxes; messages name the Excel row.
     *
     * @param  array{department: Department, boxes: list<array<string, mixed>>}  $group
     * @return array{0: array<string, string>, 1: array<string, mixed>} [messages by field key, validated data]
     */
    private function check(User $user, array $group, bool $isDraft, ?UploadedFile $scan, bool $allowNoScan = false): array
    {
        $payload = ['submit_action' => $isDraft ? 'draft' : 'submit', 'department_id' => $group['department']->id,
            'tgl_penyerahan' => now()->format('Y-m-d'), 'boxes' => $this->formBoxes($group['boxes'])];
        $form = ArchiveSubmissionRequest::create('/archives', 'POST', $payload, [], $scan ? ['scan_input_form' => $scan] : []);
        $form->setUserResolver(fn (): User => $user);
        if ($allowNoScan) {
            $form->attributes->set('skip_scan_check', true);
        }
        $validator = Validator::make($form->all(), $form->rules(), [], self::ATTRIBUTES)->after($form->after());

        $messages = [];
        foreach ($validator->errors()->messages() as $key => $list) {
            $messages[$key] = $this->rowOf($group['boxes'], $key).$list[0];
        }

        return [$messages, $messages === [] ? $validator->validated() : []];
    }

    /**
     * Check-in imported admin boxes directly to the specified rack & optional slot.
     *
     * @param  list<Archive>  $saved
     * @param  list<array<string, mixed>>  $boxes
     */
    private function checkinImportedBoxes(User $user, array $saved, array $boxes): void
    {
        foreach ($saved as $index => $archive) {
            $meta = $boxes[$index] ?? null;
            $rackCode = trim((string) ($meta['raw_rack'] ?? ''));
            if ($rackCode === '') {
                throw new DomainException("Baris {$meta['row']}: Kolom LOKASI_RAK wajib diisi untuk status Tersimpan di Gudang.");
            }
            $room = trim((string) ($meta['raw_room'] ?? ''));
            $rack = WarehouseLocation::query()->with('warehouse')
                ->where('location_type', 'rack')->where('is_active', true)
                ->whereRaw('upper(rack_code) = ?', [strtoupper($rackCode)])
                ->when($room !== '', fn ($q) => $q->where(function ($q) use ($room): void {
                    $q->whereRaw('upper(room_sector) = ?', [strtoupper($room)])
                        ->orWhereHas('warehouse', fn ($w) => $w->whereRaw('upper(code) = ?', [strtoupper($room)]));
                }))
                ->first();
            if (! $rack) {
                throw new DomainException("Baris {$meta['row']}: Lokasi rak '{$rackCode}' tidak ditemukan atau tidak aktif.");
            }

            $slotId = null;
            $slotRaw = trim((string) ($meta['raw_slot'] ?? ''));
            if ($slotRaw !== '') {
                $slot = WarehouseRackSlot::query()->where('warehouse_location_id', $rack->id)
                    ->where(fn ($q) => $q->whereRaw('upper(slot_code) = ?', [strtoupper($slotRaw)])->orWhere('slot_number', ctype_digit($slotRaw) ? (int) $slotRaw : -1))
                    ->first();
                if (! $slot) {
                    throw new DomainException("Baris {$meta['row']}: Slot '{$slotRaw}' tidak ditemukan di rak {$rack->rack_code}.");
                }
                $slotId = $slot->id;
            }

            $rawBox = trim((string) ($meta['raw_box'] ?? ''));
            if ($rawBox !== '' && str_contains($rawBox, '/')) {
                if (Archive::query()->where('box_number', $rawBox)->exists()) {
                    throw new DomainException("Baris {$meta['row']}: Nomor box '{$rawBox}' sudah digunakan.");
                }
                $archive->box_number = $rawBox;
            }
            $archive->status = 'approved_booked';
            $archive->save();

            $this->checkin->handle($user, $archive, $rack->id, 'Impor massal data existing ke gudang.', $slotId);
        }
    }

    /**
     * "Baris N: " for a box or item key (boxes.0.items.2.notes), '' otherwise.
     *
     * @param  list<array<string, mixed>>  $boxes
     */
    private function rowOf(array $boxes, string $key): string
    {
        if (! preg_match('/^boxes\.(\d+)(?:\.items\.(\d+))?/', $key, $m)) {
            return '';
        }
        $box = $boxes[(int) $m[1]] ?? null;
        $row = isset($m[2]) ? ($box['items'][(int) $m[2]]['row'] ?? null) : ($box['row'] ?? null);

        return $row ? "Baris {$row}: " : '';
    }

    /**
     * @param  list<array<string, mixed>>  $boxes
     * @return array<int, array{department: Department, boxes: list<array<string, mixed>>}>
     */
    private function groups(array $boxes): array
    {
        $groups = [];
        foreach ($boxes as $box) {
            $groups[$box['department']->id]['department'] = $box['department'];
            $groups[$box['department']->id]['boxes'][] = $box;
        }

        return $groups;
    }

    /**
     * Boxes as the form posts them (empty text = null, like the HTTP middleware).
     *
     * @param  list<array<string, mixed>>  $boxes
     * @return list<array<string, mixed>>
     */
    private function formBoxes(array $boxes): array
    {
        return array_map(fn (array $box): array => [
            'title' => $box['title'],
            'sub_department_id' => $box['sub_department_id'],
            'document_type' => $box['document_type'],
            'physical_condition' => $box['physical_condition'],
            'periode' => $box['periode'],
            'items' => array_map(fn (array $item): array => [
                'document_name' => $item['document_name'],
                'period_start' => $item['period_start'],
                'period_end' => $item['period_end'],
                'notes' => $item['notes'] !== '' ? $item['notes'] : null,
            ], $box['items']),
        ], $boxes);
    }

    /**
     * Laravel 7 preview box, with the values the form rules will store (ArchiveBoxBuilder).
     *
     * @param  array<string, mixed>  $box
     * @return array<string, mixed>
     */
    private function previewBox(array $box): array
    {
        $items = $this->builder->items($box['items'], true);
        $values = $this->builder->boxAttributes($box, $items, $box['department'], now());

        return [
            'temp_box_id' => $box['temp_box_id'], 'title' => $box['title'], 'name' => $box['title'],
            'department_id' => $box['department']->id, 'department_code' => $box['department']->code, 'department_name' => $box['department']->name,
            'sub_department_id' => $box['sub_department_id'], 'sub_department_code' => $box['sub_department_code'],
            'document_type' => $box['document_type'], 'physical_condition' => $box['physical_condition'],
            'periode' => $values['periode'], 'periode_doc' => $values['periode_doc'],
            'period_start_date' => $values['period_start_date'], 'period_end_date' => $values['period_end_date'],
            'retention_years' => $values['retention_years'], 'retention_expiry_date' => $values['retention_expiry_date'],
            'items_count' => count($items),
            'items' => array_map(fn (array $it): array => array_intersect_key($it, array_flip(['item_number', 'document_name', 'period_start', 'period_end', 'period_text', 'notes'])), $items),
        ];
    }
}
