@extends('layouts.admin')

@section('title', 'Tambah Merek')
@section('page-title', 'Tambah Merek')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.merek.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="mb-3">
         <label for="nama_merek" class="form-label fw-semibold">Nama Merek <span class="text-danger">*</span></label>
         <input type="text" name="nama_merek" id="nama_merek" class="form-control rounded-3" value="{{ old('nama_merek') }}" required>
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug (Opsional)</label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug') }}" placeholder="Otomatis dibuat dari nama merek jika kosong">
      </div>

      <div class="mb-3">
         <label for="logo" class="form-label fw-semibold">Upload Logo Merek</label>
         <input type="file" name="logo" id="logo" class="form-control rounded-3">
      </div>

      <div class="mb-3">
         <label for="deskripsi" class="form-label fw-semibold">Deskripsi (Indonesia)</label>
         <textarea name="deskripsi" id="deskripsi" rows="3" class="form-control rounded-3">{{ old('deskripsi') }}</textarea>
      </div>

      <div class="mb-3">
         <label for="deskripsi_eng" class="form-label fw-semibold">Deskripsi (English)</label>
         <textarea name="deskripsi_eng" id="deskripsi_eng" rows="3" class="form-control rounded-3">{{ old('deskripsi_eng') }}</textarea>
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Merek</button>
         <a href="{{ route('admin.merek.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
