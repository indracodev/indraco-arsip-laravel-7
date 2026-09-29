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

        foreach ($archives as $archive) {
            if ($archive->items->isEmpty()) {
                if (!empty($archive->content_description)) {
                    $lines = array_filter(array_map('trim', explode("\n", $archive->content_description)));
                    $num = 1;
                    foreach ($lines as $line) {
                        $clean = ltrim($line, "-* \t0..9.");
                        if (!empty($clean)) {
                            ArchiveItem::create([
                                'archive_id' => $archive->id,
                                'item_number' => $num++,
                                'document_name' => $clean,
                                'period_text' => $archive->periode_doc ?? $archive->period_text ?? '2026/09',
                            ]);
                            $seededCount++;
                        }
                    }
                } else {
                    ArchiveItem::create([
                        'archive_id' => $archive->id,
                        'item_number' => 1,
                        'document_name' => $archive->effective_title ?? 'Dokumen Berkas ' . $archive->box_number,
                        'period_text' => $archive->periode_doc ?? $archive->period_text ?? '2026/09',
                    ]);
                    $seededCount++;
                }
            }
        }

        echo "Total synced items: {$seededCount}\n";
    }
}
