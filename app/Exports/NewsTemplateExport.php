<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NewsTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'Peluncuran Supresso Speciality Series',
                'Launch of Supresso Speciality Series',
                'peluncuran-supresso-speciality-series',
                '15 Mei 2026',
                '15 May 2026',
                'INDRACO meluncurkan varian kopi Nusantara terbaru.',
                'INDRACO launches the newest Nusantara coffee variant.',
                'images/uploads/news/sample.jpg',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'judul',
            'judul_eng',
            'slug',
            'tanggal',
            'tanggal_eng',
            'content',
            'content_eng',
            'image_path',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
