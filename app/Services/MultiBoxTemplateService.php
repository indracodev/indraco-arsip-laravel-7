<?php

namespace App\Services;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\WarehouseLocation;
use App\Models\User;
use ZipArchive;

class MultiBoxTemplateService
{
    /**
     * Generate template XLSX or CSV with sample multi-box data and reference guide.
     */
    public function generateTemplate(string $format = 'xlsx', ?User $user = null)
    {
        $isPicDept = ($user && $user->isPicDept());

        if ($isPicDept) {
            // Tampilan / Hak Akses Departemen: Pure data box, isi berkas, dan kode dept (Tanpa kolom rak gudang)
            $headers = [
                'NO_BOX_SEMENTARA',
                'JUDUL_BOX',
                'KODE_DEPARTEMEN',
                'KODE_SUB_DEPARTEMEN',
                'NAMA_DOKUMEN',
                'PERIODE_DOK_MULAI',
                'PERIODE_DOK_SELESAI',
                'MASA_SIMPAN_BULAN',
                'KONDISI_FISIK',
                'NO_BUTIR',
                'KETERANGAN_BUTIR',
            ];
        } else {
            // Tampilan Gudang / Admin: Lengkap dengan kolom alokasi rak fisik gudang
            $headers = [
                'NO_BOX_SEMENTARA',
                'JUDUL_BOX',
                'KODE_DEPARTEMEN',
                'KODE_SUB_DEPARTEMEN',
                'NAMA_DOKUMEN',
                'PERIODE_DOK_MULAI',
                'PERIODE_DOK_SELESAI',
                'MASA_SIMPAN_BULAN',
                'KONDISI_FISIK',
                'NO_BUTIR',
                'LOKASI_RUANG',
                'LOKASI_RAK',
                'NO_SLOT',
                'KETERANGAN_BUTIR',
            ];
        }

        // Scope-aware sample rows: Identitas box cukup diisi satu kali pada baris pertama box. Baris butir berikutnya cukup nama dokumen. No butir auto jika kosong.
        if ($isPicDept && $user->department_id) {
            $userDept = $user->department ?: Department::find($user->department_id);
            $userDeptCode = $userDept ? $userDept->code : 'DEPT';
            $userDeptName = $userDept ? $userDept->name : 'Departemen';
            $userSubDepts = SubDepartment::where('department_id', $user->department_id)->where('is_active', true)->orderBy('code')->get();
            $subDeptList = $userSubDepts->pluck('code')->toArray();
            $firstSubDeptCode = $subDeptList[0] ?? ($userDeptCode === 'HRD' ? 'GA' : 'GEN');
            $secondSubDeptCode = $subDeptList[1] ?? ($userDeptCode === 'HRD' ? 'LGL' : $firstSubDeptCode);

            $sampleRows = [
                // Box 1: Baris Pertama - Mengisi Identitas Box Lengkap (Masa simpan angka bulan murni: 60)
                [
                    'BOX-01',
                    "Berkas Operasional {$userDeptName} Jan 2024",
                    $userDeptCode,
                    $firstSubDeptCode,
                    'Dokumen Administrasi Utama Bagian 01',
                    '2024-01',
                    '2024-01',
                    '60',
                    'Baik / Box Karton Standar TB 30g',
                    '1',
                    'Asli bertandatangan',
                ],
                // Box 1: Baris Kedua - Identitas Box Dikosongkan (Cukup isi sekali per box) & No Butir Auto
                [
                    '', // NO_BOX_SEMENTARA (Otomatis mewarisi BOX-01)
                    '', // JUDUL_BOX (Otomatis mewarisi judul box di atas)
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Dokumen Administrasi Utama Bagian 02',
                    '2024-01',
                    '2024-01',
                    '', // MASA_SIMPAN_BULAN (Otomatis mewarisi 60)
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 2 oleh sistem)
                    'Asli bertandatangan',
                ],

                // Box 2: Baris Pertama - Box Baru (Masa simpan 12 bulan)
                [
                    'BOX-02',
                    "Laporan & Formulir {$userDeptName} Periode 2025",
                    $userDeptCode,
                    $secondSubDeptCode,
                    'Formulir Kegiatan & Laporan Rekapitulasi',
                    'Jan 25',
                    'Des 25',
                    '12',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 1 oleh sistem)
                    'Lengkap',
                ],
                // Box 2: Baris Kedua - Identitas Box Dikosongkan & No Butir Auto
                [
                    '', // NO_BOX_SEMENTARA (Otomatis mewarisi BOX-02)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Daftar Checklist & Lampiran Pendukung',
                    'Jan 25',
                    'Des 25',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Dikosongkan: otomatis di-generate 2 oleh sistem)
                    'Lengkap',
                ],
            ];
        } else {
            $sampleRows = [
                // Box 1: Keuangan (Finance) - Baris Pertama (Masa simpan angka bulan murni: 60)
                [
                    'BOX-01',
                    'Voucher Pengeluaran Kas & Bank Jan 2024',
                    'FIN',
                    'KAS',
                    'Kwitansi & Bukti Kas Keluar No. 001 - 050',
                    '2024-01',
                    '2024-01',
                    '60',
                    'Baik / Box Karton Standar TB 30g',
                    '1',
                    'R5',
                    'R5-AS',
                    '1',
                    'Asli bertandatangan Kasir & Mgr',
                ],
                // Box 1: Baris Kedua - Identitas Box Cukup Sekali & No Butir Dikosongkan (Auto Urut)
                [
                    '', // NO_BOX_SEMENTARA (Mewarisi BOX-01)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Kwitansi & Bukti Kas Keluar No. 051 - 100',
                    '2024-01',
                    '2024-01',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Auto urut: 2)
                    '', // RUANG (Mewarisi R5)
                    '', // RAK (Mewarisi R5-AS)
                    '', // SLOT (Mewarisi 1)
                    'Asli bertandatangan Kasir & Mgr',
                ],

                // Box 2: HCS / GA - Baris Pertama (Masa simpan angka bulan murni: 12)
                [
                    'BOX-02',
                    'Berkas Formulir Utilitas & Maintenance GA 2025',
                    'HCS',
                    'GA',
                    'Form Utilitas & Perawatan AC',
                    'Jan 25',
                    'Des 25',
                    '12',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Auto urut: 1)
                    'R5',
                    'R5-AT',
                    '2',
                    'Karyawan Kantor Pusat',
                ],
                // Box 2: Baris Kedua - Identitas Box Cukup Sekali & No Butir Dikosongkan
                [
                    '', // NO_BOX_SEMENTARA (Mewarisi BOX-02)
                    '', // JUDUL_BOX
                    '', // KODE_DEPARTEMEN
                    '', // KODE_SUB_DEPARTEMEN
                    'Data Maintenance Kendaraan Operasional',
                    'Jan 25',
                    'Des 25',
                    '', // MASA_SIMPAN_BULAN
                    '', // KONDISI_FISIK
                    '', // NO_BUTIR (Auto urut: 2)
                    '', // RUANG
                    '', // RAK
                    '', // SLOT
                    'Armada Operasional Logistik',
                ],

                // Box 3: Akuntansi (Accounting) - Baris Pertama (Masa simpan angka bulan murni: 120 = 10 Tahun)
                [
                    'BOX-03',
                    'Faktur Pajak Masukan & Keluaran Masa Pajak 2024',
                    'ACC',
                    'TAX',
                    'Faktur Pajak Keluaran Masa Jan - Jun 2024',
                    '2024-01',
                    '2024-06',
                    '120',
                    'Baik / Box Karton Standar TB 30g',
                    '', // NO_BUTIR (Auto urut: 1)
                    'R5',
                    'R5-AU',
                    '1',
                    'Sudah divalidasi DJP e-Faktur',
                ],
            ];
        }

        if (strtolower($format) === 'csv') {
            return $this->buildCsvResponse($headers, $sampleRows, 'template_arsip_multi_box.csv');
        }

        return $this->buildXlsxResponse($headers, $sampleRows, 'template_arsip_multi_box.xlsx', $user);
    }

    /**
     * Build enhanced multi-sheet XLSX template.
     */
    public function buildXlsxResponse(array $headers, array $rows, string $filename, ?User $user = null)
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'dms_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
            '<sheet name="DATA_ARSIP" sheetId="1" r:id="rId1"/>' .
            '<sheet name="REFERENSI_KODE" sheetId="2" r:id="rId2"/>' .
            '</sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/worksheets/sheet1.xml (DATA_ARSIP)
        $defaultWidths = [
            'NO_BOX_SEMENTARA' => 22,
            'JUDUL_BOX' => 48,
            'KODE_DEPARTEMEN' => 22,
            'KODE_SUB_DEPARTEMEN' => 26,
            'NAMA_DOKUMEN' => 46,
            'PERIODE_DOK_MULAI' => 24,
            'PERIODE_DOK_SELESAI' => 24,
            'MASA_SIMPAN_BULAN' => 22,
            'KONDISI_FISIK' => 36,
            'NO_BUTIR' => 14,
            'LOKASI_RUANG' => 18,
            'LOKASI_RAK' => 18,
            'NO_SLOT' => 14,
            'KETERANGAN_BUTIR' => 32,
        ];

        $colsXml = '<cols>';
        foreach ($headers as $cIdx => $hdr) {
            $colNum = $cIdx + 1;
            $maxLen = strlen((string)$hdr);
            foreach ($rows as $r) {
                if (isset($r[$cIdx])) {
                    $len = strlen((string)$r[$cIdx]);
                    if ($len > $maxLen) {
                        $maxLen = $len;
                    }
                }
            }
            $baseWidth = $defaultWidths[$hdr] ?? 20;
            $width = max($baseWidth, $maxLen + 4);
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $colsXml .
            '<sheetData>';

        $allData = array_merge([$headers], $rows);
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P'];

        foreach ($allData as $rIdx => $row) {
            $rNum = $rIdx + 1;
            $sheetXml .= '<row r="' . $rNum . '">';
            foreach ($row as $cIdx => $val) {
                $col = $colLetters[$cIdx] ?? 'A';
                $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                $sheetXml .= '<c r="' . $col . $rNum . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        // 6. xl/worksheets/sheet2.xml (REFERENSI_KODE) - Scoped by User Department
        $isPicDept = ($user && $user->isPicDept() && $user->department_id);
        $userDept = $isPicDept ? ($user->department ?: Department::find($user->department_id)) : null;

        if ($isPicDept && $userDept) {
            $departments = Department::where('id', $userDept->id)->get();
            $subDepts = SubDepartment::where('department_id', $userDept->id)->where('is_active', true)->with('department')->orderBy('code')->get();
            $locations = WarehouseLocation::where('is_active', true)
                ->where(function ($q) use ($userDept) {
                    $q->where('assigned_department_id', $userDept->id)
                      ->orWhereNull('assigned_department_id');
                })
                ->orderBy('rack_code')
                ->take(30)
                ->get();
        } else {
            $departments = Department::where('is_active', true)->orderBy('code')->get();
            $subDepts = SubDepartment::where('is_active', true)->with('department')->orderBy('code')->get();
            $locations = WarehouseLocation::where('is_active', true)->orderBy('rack_code')->take(30)->get();
        }

        $sheet2Cols = '<cols>' .
            '<col min="1" max="1" width="28" customWidth="1"/>' .
            '<col min="2" max="2" width="42" customWidth="1"/>' .
            '<col min="3" max="3" width="58" customWidth="1"/>' .
            '<col min="4" max="4" width="26" customWidth="1"/>' .
            '</cols>';

        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $sheet2Cols .
            '<sheetData>';

        $r2 = 1;
        if ($isPicDept && $userDept) {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>BUKU PANDUAN REFERENSI KODE RESMI - DEPARTEMEN ' . htmlspecialchars($userDept->name, ENT_XML1, 'UTF-8') . ' (' . htmlspecialchars($userDept->code, ENT_XML1, 'UTF-8') . ')</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>Akun Anda memiliki hak akses PIC Departemen ' . htmlspecialchars($userDept->name, ENT_XML1, 'UTF-8') . '. Sesuai batasan otoritas akun, tabel di bawah HANYA menampilkan kode resmi departemen Anda agar terhindar dari salah pilih kode departemen lain.</t></is></c></row>';
        } else {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>BUKU PANDUAN REFERENSI KODE RESMI &amp; MASTER DATA DMS INDRACO</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>Gunakan kode-kode resmi di bawah ini saat mengisi sheet DATA_ARSIP agar proses impor berjalan lancar 100%.</t></is></c></row>';
        }
        $r2 += 2;

        // Sub-table: Departemen
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[1. KODE DEPARTEMEN RESMI ANDA]</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '">' .
            '<c r="A' . $r2 . '" t="inlineStr"><is><t>KODE_DEPARTEMEN</t></is></c>' .
            '<c r="B' . $r2 . '" t="inlineStr"><is><t>NAMA_DEPARTEMEN</t></is></c>' .
            '<c r="C' . $r2 . '" t="inlineStr"><is><t>DEFAULT_RETENSI_TAHUN</t></is></c>' .
            '</row>';
        $r2++;

        foreach ($departments as $d) {
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($d->code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($d->name, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars((string)($d->retention_years ?: 5), ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '</row>';
            $r2++;
        }

        $r2++;
        // Sub-table: Sub-Departemen
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[2. DAFTAR KODE SUB-DEPARTEMEN RESMI]</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '">' .
            '<c r="A' . $r2 . '" t="inlineStr"><is><t>KODE_SUB_DEPARTEMEN</t></is></c>' .
            '<c r="B' . $r2 . '" t="inlineStr"><is><t>NAMA_SUB_DEPARTEMEN</t></is></c>' .
            '<c r="C' . $r2 . '" t="inlineStr"><is><t>DEPARTEMEN_INDUK</t></is></c>' .
            '</row>';
        $r2++;

        if ($subDepts->isEmpty()) {
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>-(TIDAK ADA)-</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>Departemen ini tidak memiliki sub-departemen. Kolom KODE_SUB_DEPARTEMEN boleh dikosongkan.</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($userDept ? $userDept->code : '-', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '</row>';
            $r2++;
        } else {
            foreach ($subDepts as $sd) {
                $sheet2Xml .= '<row r="' . $r2 . '">' .
                    '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->name, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($sd->department->code ?? '', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '</row>';
                $r2++;
            }
        }

        $r2++;
        if (!$isPicDept) {
            // Sub-table: Gudang & Rak (Hanya untuk Admin / Gudang)
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>[3. DAFTAR RUANG &amp; RAK GUDANG AKTIF (CONTOH)]</t></is></c></row>';
            $r2++;
            $sheet2Xml .= '<row r="' . $r2 . '">' .
                '<c r="A' . $r2 . '" t="inlineStr"><is><t>RUANG / SEKTOR</t></is></c>' .
                '<c r="B' . $r2 . '" t="inlineStr"><is><t>KODE_RAK</t></is></c>' .
                '<c r="C' . $r2 . '" t="inlineStr"><is><t>KETERANGAN_LOKASI</t></is></c>' .
                '</row>';
            $r2++;

            foreach ($locations as $loc) {
                $sheet2Xml .= '<row r="' . $r2 . '">' .
                    '<c r="A' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->room_sector ?? 'GUDANG', ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="B' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->clean_rack_code ?? $loc->rack_code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '<c r="C' . $r2 . '" t="inlineStr"><is><t>' . htmlspecialchars($loc->full_location ?? $loc->rack_code, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                    '</row>';
                $r2++;
            }
            $r2++;
        }

        // Sub-table: Petunjuk Pengisian
        $guideHeader = $isPicDept ? '[3. PETUNJUK PENGISIAN PENGAJUAN ARSIP]' : '[4. PETUNJUK PENGISIAN AGAR MINIM ERROR]';
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>' . $guideHeader . '</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>1. Identitas Box (NO_BOX_SEMENTARA, JUDUL_BOX, DEPARTEMEN) cukup diisi 1 kali pada baris pertama box. Baris butir berikutnya dalam box yang sama cukup dikosongkan.</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>2. Kolom NO_BUTIR boleh dikosongkan jika ingin nomor urut dibuat otomatis (1, 2, 3...) oleh sistem.</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>3. Kolom MASA_SIMPAN_BULAN murni hitungan bulan (contoh: 60 untuk 60 bulan/5 tahun, 12 untuk 12 bulan/1 tahun).</t></is></c></row>';
        $r2++;
        $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>4. Format Periode Mulai &amp; Selesai disarankan YYYY-MM (contoh: 2024-01) atau format bulan natural (contoh: Jan 25 s/d Des 25).</t></is></c></row>';
        $r2++;
        if ($isPicDept) {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>5. Penentuan lokasi rak dan slot gudang fisik tidak perlu diisi oleh departemen. Alokasi rak akan diverifikasi dan diplot langsung oleh Tim Gudang saat proses persetujuan (approval).</t></is></c></row>';
        } else {
            $sheet2Xml .= '<row r="' . $r2 . '"><c r="A' . $r2 . '" t="inlineStr"><is><t>5. Kolom Lokasi (RUANG, RAK, SLOT) bersifat opsional. Diisi jika berkas merupakan arsip lama yang sudah tersusun di rak fisik gudang.</t></is></c></row>';
        }

        $sheet2Xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);

        $zip->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build clean CSV file response with UTF-8 BOM.
     */
    public function buildCsvResponse(array $headers, array $rows, string $filename)
    {
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $headers);
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
        rewind($f);
        $output .= stream_get_contents($f);
        fclose($f);

        return response($output, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
