<?php

namespace App\Imports;

use App\Models\MasterNews;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class NewsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $judul = trim($row['judul'] ?? '');
        if (empty($judul)) {
            return null;
        }

        $slug = !empty($row['slug']) ? Str::slug($row['slug']) : Str::slug($judul);

        return MasterNews::updateOrCreate(
            ['slug' => $slug],
            [
                'judul' => $judul,
                'judul_eng' => $row['judul_eng'] ?? $row['judul_en'] ?? null,
                'tanggal' => $row['tanggal'] ?? date('d M Y'),
                'tanggal_eng' => $row['tanggal_eng'] ?? $row['tanggal_en'] ?? null,
                'content' => $row['content'] ?? $row['isi_berita'] ?? null,
                'content_eng' => $row['content_eng'] ?? $row['isi_berita_eng'] ?? null,
                'image_path' => $row['image_path'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            '*.judul' => 'required|string',
        ];
    }
}
