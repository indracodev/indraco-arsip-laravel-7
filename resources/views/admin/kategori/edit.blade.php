@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.kategori.update', $kategori->id) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="mb-3">
         <label for="nama_kategori" class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
         <input type="text" name="nama_kategori" id="nama_kategori" class="form-control rounded-3" value="{{ old('nama_kategori', $kategori->nama_kategori) }}" required>
      </div>

      <div class="mb-3">
         <label for="parent_id" class="form-label fw-semibold">Parent Kategori (Opsional)</label>
         <select name="parent_id" id="parent_id" class="form-select rounded-3">
            <option value="">-- Tanpa Parent (Kategori Utama) --</option>
            @foreach($parents as $parent)
               <option value="{{ $parent->id }}" {{ old('parent_id', $kategori->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->nama_kategori }}</option>
            @endforeach
         </select>
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug', $kategori->slug) }}" required>
      </div>

      <div class="mb-3">
         <label for="urutan" class="form-label fw-semibold">Urutan Display</label>
         <input type="number" name="urutan" id="urutan" class="form-control rounded-3" value="{{ old('urutan', $kategori->urutan) }}">
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status', $kategori->status) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $kategori->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Update Kategori</button>
         <a href="{{ route('admin.kategori.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
