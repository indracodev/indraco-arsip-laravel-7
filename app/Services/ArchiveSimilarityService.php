<?php

namespace App\Services;

use App\Models\Archive;

/**
 * Service untuk pengecekan kemiripan dan duplikasi judul box arsip secara live.
 */
class ArchiveSimilarityService
{
    /**
     * Cek kemiripan judul arsip terhadap arsip lain dalam satu departemen.
     */
    public function checkSimilarity(string $title, int $deptId, array $excludeIds = []): array
    {
        $title = trim($title);
        if (empty($title) || empty($deptId)) {
            return [
                'has_similarity' => false,
                'message' => 'Judul atau departemen kosong',
            ];
        }

        // Normalisasi teks
        $normalizedInput = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $title));
        $normalizedInput = preg_replace('/\s+/', ' ', trim($normalizedInput));

        $excludeIds = array_unique(array_filter($excludeIds));

        $query = Archive::where('department_id', $deptId)
            ->where('status', '!=', 'destroyed');

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $bestMatch = null;
        $highestSimilarity = 0;
        $matchType = 'unique';

        // 1. Direct exact match check across entire department
        $exactMatch = (clone $query)->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($title))])->first();
        if ($exactMatch) {
            $bestMatch = $exactMatch;
            $highestSimilarity = 100;
            $matchType = 'identical';
        } else {
            $existingArchives = $query->select(['id', 'box_number', 'title', 'status', 'periode_doc', 'periode', 'period_text'])
                ->latest('id')
                ->take(100)
                ->get();

            foreach ($existingArchives as $arch) {
                $existingTitle = trim($arch->title ?? '');
                if (empty($existingTitle)) continue;

                $normalizedExisting = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $existingTitle));
                $normalizedExisting = preg_replace('/\s+/', ' ', trim($normalizedExisting));

                if ($normalizedInput === $normalizedExisting) {
                    $bestMatch = $arch;
                    $highestSimilarity = 100;
                    $matchType = 'identical';
                    break;
                }

                similar_text($normalizedInput, $normalizedExisting, $percent);
                if ($percent > $highestSimilarity) {
                    $highestSimilarity = round($percent, 1);
                    $bestMatch = $arch;
                }
            }
        }

        if ($highestSimilarity >= 70 && $bestMatch) {
            if ($highestSimilarity < 100) {
                $matchType = 'similar';
            }

            // Word difference highlights
            $inputWords = explode(' ', $normalizedInput);
            $existingWords = explode(' ', strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $bestMatch->title)));
            $diffWordsInput = array_diff($inputWords, $existingWords);
            $diffWordsExisting = array_diff($existingWords, $inputWords);

            return [
                'has_similarity' => true,
                'is_duplicate' => ($matchType === 'identical'),
                'match_type' => $matchType,
                'similarity_percentage' => $highestSimilarity,
                'matched_archive' => [
                    'id' => $bestMatch->id,
                    'box_number' => $bestMatch->box_number,
                    'title' => $bestMatch->title,
                    'status' => $bestMatch->status,
                    'periode_doc' => $bestMatch->periode_doc,
                    'period_text' => $bestMatch->period_text,
                ],
                'diff' => [
                    'unique_in_input' => array_values($diffWordsInput),
                    'unique_in_existing' => array_values($diffWordsExisting),
                ],
            ];
        }

        return [
            'has_similarity' => false,
            'match_type' => 'unique',
            'similarity_percentage' => $highestSimilarity,
        ];
    }
}
