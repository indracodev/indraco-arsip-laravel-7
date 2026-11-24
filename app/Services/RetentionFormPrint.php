<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\User;

/**
 * Printed "Form Pemusnahan" / "Form Perpanjangan" of the propose / extend forms (Laravel 7
 * destructions/propose + extend_scripts openFormPengajuanModal / populateFormRows), on the
 * shared A4 paper of archives/form-print.blade.php. Rows come from the chosen saved boxes.
 */
final class RetentionFormPrint
{
    public function __construct(private RetentionCalculator $calculator, private BorrowingFormPrint $borrowing) {}

    /**
     * @param  list<Archive>  $archives  boxes the user may see, in the chosen order
     * @param  int|null  $months  extension months (null = Pemusnahan)
     * @return array{fields: array<string, string>, rows: list<array{no: int, title: string, sub_dept: string, masa_simpan: string, period_end: string}>}
     */
    public function for(User $user, array $archives, ?int $months, string $text): array
    {
        $print = $this->borrowing->for($user, $archives, null); // same header (department / sub-department) and blank rows
        foreach ($archives as $i => $archive) {
            $print['rows'][$i]['masa_simpan'] = $months === null ? 'Habis' : $months.' Bln';
            $print['rows'][$i]['period_end'] = $months === null
                ? ($this->calculator->expiryEnd($archive)?->format('M Y') ?? '-')
                : $this->calculator->extendedExpiry($archive, $months)['new']->format('m-Y');
        }
        $print['fields']['tipe'] = $months === null ? 'Pemusnahan' : 'Perpanjangan';
        $print['fields']['catatan'] = $months === null
            ? 'Usulan pemusnahan fisik berkas arsip. Catatan: '.$text
            : "Perpanjangan masa simpan ({$months} Bulan). Alasan: ".$text;

        return $print;
    }
}
