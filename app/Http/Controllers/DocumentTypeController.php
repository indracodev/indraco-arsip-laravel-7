<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = DocumentType::with(['department', 'creator']);

        if ($user && $user->isPicDept()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $user->department_id);
            });
        }

        $documentTypes = $query->orderBy('is_preset', 'desc')
            ->orderBy('code', 'asc')
            ->get();

        $departments = Department::all();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $documentTypes,
            ]);
        }

        return view('master.document_types', compact('documentTypes', 'departments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:document_types,code',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
        ], [
            'code.required' => 'Kode / Singkatan tipe dokumen wajib diisi.',
            'code.unique' => 'Kode dokumen ini sudah terdaftar dalam katalog.',
            'name.required' => 'Nama tipe dokumen wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);
        $validated['created_by_user_id'] = $user ? $user->id : null;
        $validated['is_preset'] = false;

        // If PIC Dept, lock or default to user's department if scope is dept-specific
        if ($user && $user->isPicDept() && $request->filled('scope_department') && $request->scope_department === 'dept') {
            $validated['department_id'] = $user->department_id;
        }

        $docType = DocumentType::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'document_type' => [
                    'id' => $docType->code,
                    'db_id' => $docType->id,
                    'code' => $docType->code,
                    'name' => $docType->name,
                    'desc' => $docType->description ?? $docType->name,
                    'is_preset' => $docType->is_preset,
                ],
                'message' => "Tipe dokumen {$docType->name} ({$docType->code}) berhasil ditambahkan ke katalog.",
            ]);
        }

        return redirect()->route('master.document_types')
            ->with('success', "Tipe dokumen {$docType->name} ({$docType->code}) berhasil ditambahkan ke katalog.");
    }

    public function apiStore(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
        return $this->store($request);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:document_types,code,' . $documentType->id,
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $documentType->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'document_type' => $documentType,
                'message' => "Tipe dokumen {$documentType->name} berhasil diperbarui.",
            ]);
        }

        return redirect()->route('master.document_types')
            ->with('success', "Tipe dokumen {$documentType->name} berhasil diperbarui.");
    }

    public function destroy(DocumentType $documentType)
    {
        if ($documentType->is_preset) {
            return back()->with('error', "Tipe dokumen preset bawaan sistem ({$documentType->name}) tidak dapat dihapus.");
        }

        $documentType->delete();

        return redirect()->route('master.document_types')
            ->with('success', "Tipe dokumen {$documentType->name} berhasil dihapus dari katalog.");
    }
}
