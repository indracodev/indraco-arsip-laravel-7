<?php

namespace App\Services;

use App\Models\Archive;

/**
 * Live "cek kemiripan" of a box title against the department's archives (not destroyed), same
 * result shape as Laravel 7 ArchiveSimilarityService: identical = 100 %, similar >= 70 %
 * (similar_text over the latest 100 titles). Informative only; the hard rule (identical titles
 * refused) is enforced again on submit by SubmitArchiveBoxes.
 */
final class ArchiveSimilarity
{
    /**
     * @param  list<int>  $excludeIds
     * @return array<string, mixed>
     */
    public function check(string $title, int $departmentId, array $excludeIds = []): array
    {
        $title = trim($title);
        if ($title === '' || $departmentId < 1) {
            return ['has_similarity' => false, 'message' => 'Judul atau departemen kosong'];
        }
        $input = $this->normalize($title);
        $query = Archive::query()->where('department_id', $departmentId)->where('status', '!=', 'destroyed')
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds));
        $columns = ['id', 'box_number', 'title', 'status', 'periode_doc', 'period_text'];

        $best = (clone $query)->whereRaw('LOWER(TRIM(title)) = ?', [strtolower($title)])->first($columns);
        $score = $best ? 100.0 : 0.0;
        if (! $best) {
            foreach ($query->latest('id')->take(100)->get($columns) as $archive) {
                $existing = $this->normalize((string) $archive->title);
                if ($existing === '') {
                    continue;
                }
                if ($existing === $input) {
                    [$best, $score] = [$archive, 100.0];
                    break;
                }
                similar_text($input, $existing, $percent);
                if ($percent > $score) {
                    [$best, $score] = [$archive, round($percent, 1)];
                }
            }
        }

        if ($score < 70 || ! $best) {
            return ['has_similarity' => false, 'match_type' => 'unique', 'similarity_percentage' => $score];
        }
        $inputWords = explode(' ', $input);
        $existingWords = explode(' ', strtolower((string) preg_replace('/[^\p{L}\p{N}\s]/u', '', (string) $best->title)));

        return [
            'has_similarity' => true,
            'is_duplicate' => $score >= 100,
            'match_type' => $score >= 100 ? 'identical' : 'similar',
            'similarity_percentage' => $score,
            'matched_archive' => $best->only($columns),
            'diff' => [
                'unique_in_input' => array_values(array_diff($inputWords, $existingWords)),
                'unique_in_existing' => array_values(array_diff($existingWords, $inputWords)),
            ],
        ];
    }

    private function normalize(string $text): string
    {
        return (string) preg_replace('/\s+/', ' ', trim(strtolower((string) preg_replace('/[^\p{L}\p{N}\s]/u', '', $text))));
    }
}
