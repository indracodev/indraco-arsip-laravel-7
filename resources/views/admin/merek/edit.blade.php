@extends('layouts.admin')

@section('title', 'Edit Merek')
@section('page-title', 'Edit Merek')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.merek.update', $merek->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="mb-3">
         <label for="nama_merek" class="form-label fw-semibold">Nama Merek <span class="text-danger">*</span></label>
         <input type="text" name="nama_merek" id="nama_merek" class="form-control rounded-3" value="{{ old('nama_merek', $merek->nama_merek) }}" required>
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug', $merek->slug) }}" required>
      </div>

      <div class="mb-3">
         <label for="logo" class="form-label fw-semibold">Ganti Logo Merek</label>
         @if($merek->logo_path)
            <div class="mb-2">
               <img src="{{ asset($merek->logo_path) }}" alt="{{ $merek->nama_merek }}" style="height: 40px; object-fit: contain;">
            </div>
         @endif
         <input type="file" name="logo" id="logo" class="form-control rounded-3">
      </div>

      <div class="mb-3">
         <label for="deskripsi" class="form-label fw-semibold">Deskripsi (Indonesia)</label>
         <textarea name="deskripsi" id="deskripsi" rows="3" class="form-control rounded-3">{{ old('deskripsi', $merek->deskripsi) }}</textarea>
      </div>

      <div class="mb-3">
         <label for="deskripsi_eng" class="form-label fw-semibold">Deskripsi (English)</label>
         <textarea name="deskripsi_eng" id="deskripsi_eng" rows="3" class="form-control rounded-3">{{ old('deskripsi_eng', $merek->deskripsi_eng) }}</textarea>
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status', $merek->status) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $merek->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Update Merek</button>
         <a href="{{ route('admin.merek.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
