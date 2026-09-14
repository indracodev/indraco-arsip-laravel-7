<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\NumberingFormat;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class NumberingFormatController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
                abort(403, 'Akses khusus Super Admin.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $formats = NumberingFormat::all();
        $activeFormat = NumberingFormat::where('is_active', true)->first();

        // Sample preview archive
        $sampleArchive = new Archive([
            'period_end_date' => now(),
        ]);
        $sampleArchive->department = (object) ['code' => 'FIN'];

        $numberingService = new NumberingService();
        $previewCode = $numberingService->generateBoxCode($sampleArchive);

        return view('master.numbering', compact('formats', 'activeFormat', 'previewCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pattern' => 'required|string|max:255',
            'current_counter' => 'required|integer|min:0',
            'padding' => 'required|integer|min:1|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            NumberingFormat::query()->update(['is_active' => false]);
            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = false;
        }

        NumberingFormat::create($validated);

        return redirect()->route('master.numbering')
            ->with('success', 'Format penomoran box arsip baru berhasil ditambahkan.');
    }

    public function update(Request $request, NumberingFormat $numberingFormat)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pattern' => 'required|string|max:255',
            'current_counter' => 'required|integer|min:0',
            'padding' => 'required|integer|min:1|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            NumberingFormat::where('id', '!=', $numberingFormat->id)->update(['is_active' => false]);
            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = false;
        }

        $numberingFormat->update($validated);

        return redirect()->route('master.numbering')
            ->with('success', 'Pengaturan format penomoran box arsip berhasil diperbarui.');
    }
}
