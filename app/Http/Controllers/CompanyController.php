<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::with('creator')->latest()->get();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $companies,
            ]);
        }

        return view('master.companies', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:companies,name',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama perusahaan entitas wajib diisi.',
            'name.unique' => 'Perusahaan entitas ini sudah terdaftar.',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        $validated['name'] = trim($validated['name']);
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;

        $company = Company::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'company' => $company,
                'message' => "Perusahaan entitas {$company->name} berhasil ditambahkan.",
            ]);
        }

        return redirect()->route('master.companies')
            ->with('success', "Perusahaan entitas {$company->name} berhasil ditambahkan.");
    }

    public function apiStore(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
        return $this->store($request);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:companies,name,' . $company->id,
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        $validated['name'] = trim($validated['name']);
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        $company->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'company' => $company,
                'message' => "Data perusahaan {$company->name} berhasil diperbarui.",
            ]);
        }

        return redirect()->route('master.companies')
            ->with('success', "Data perusahaan {$company->name} berhasil diperbarui.");
    }

    public function destroy(Company $company)
    {
        // Check if archives table has records referencing this company name
        $inUse = Archive::where('company_name', $company->name)->exists();
        if ($inUse) {
            return back()->with('error', "Perusahaan {$company->name} tidak dapat dihapus karena tercatat pada berkas arsip yang sudah ada.");
        }

        $company->delete();

        return redirect()->route('master.companies')
            ->with('success', "Perusahaan entitas {$company->name} berhasil dihapus.");
    }
}
