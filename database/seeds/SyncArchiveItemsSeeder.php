<?php

use Illuminate\Database\Seeder;
use App\Models\Archive;
use App\Models\ArchiveItem;

class SyncArchiveItemsSeeder extends Seeder
{
    public function run()
    {
        $archives = Archive::with('items')->get();
        $seededCount = 0;
        $now = now();
        $itemsToInsert = [];

        foreach ($archives as $archive) {
            if ($archive->items->isEmpty()) {
                if (!empty($archive->content_description)) {
                    $lines = array_filter(array_map('trim', explode("\n", $archive->content_description)));
                    $num = 1;
                    foreach ($lines as $line) {
                        $clean = ltrim($line, "-* \t0..9.");
                        if (!empty($clean)) {
                            $itemsToInsert[] = [
                                'archive_id' => $archive->id,
                                'item_number' => $num++,
                                'document_name' => $clean,
                                'period_text' => $archive->periode_doc ?? $archive->period_text ?? '2026/09',
                                'notes' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $seededCount++;
                        }
                    }
                } else {
                    $itemsToInsert[] = [
                        'archive_id' => $archive->id,
                        'item_number' => 1,
                        'document_name' => $archive->effective_title ?? 'Dokumen Berkas ' . $archive->box_number,
                        'period_text' => $archive->periode_doc ?? $archive->period_text ?? '2026/09',
                        'notes' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $seededCount++;
                }
            }
        }

        if (!empty($itemsToInsert)) {
            foreach (array_chunk($itemsToInsert, 250) as $chunk) {
                ArchiveItem::insert($chunk);
            }
        }

        echo "Total synced items: {$seededCount}\n";
    }
}
