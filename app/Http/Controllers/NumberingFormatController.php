<?php

namespace App\Http\Controllers;

use App\Actions\Master\SaveNumberingFormat;
use App\Http\Requests\NumberingFormatRequest;
use App\Models\Archive;
use App\Models\Department;
use App\Models\NumberingFormat;
use App\Models\User;
use App\Services\NumberingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Format Penomoran Box (Laravel 7 NumberingFormatController). Admin only (routes).
 * The live preview uses NumberingService::preview(): opening the page never moves the counter.
 */
class NumberingFormatController extends Controller
{
    public function index(NumberingService $numbering): View
    {
        // Laravel 7 sample: department FIN, period ending today.
        $sample = new Archive(['period_end_date' => now()]);
        $sample->setRelation('department', new Department(['code' => 'FIN']));

        return view('master.numbering.index', [
            'formats' => NumberingFormat::query()->orderBy('id')->get(),
            'previewCode' => $numbering->preview($sample),
        ]);
    }

    public function store(NumberingFormatRequest $request, SaveNumberingFormat $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->validated());

        return redirect()->route('master.numbering')->with('success', 'Format penomoran box arsip baru berhasil ditambahkan.');
    }

    public function update(NumberingFormatRequest $request, NumberingFormat $numberingFormat, SaveNumberingFormat $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        try {
            $action->handle($user, $request->validated(), $numberingFormat);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('master.numbering')->with('success', 'Pengaturan format penomoran box arsip berhasil diperbarui.');
    }
}
