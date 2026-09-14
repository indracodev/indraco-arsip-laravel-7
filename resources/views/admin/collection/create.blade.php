@extends('layouts.admin')

@section('title', 'Tambah Collection')
@section('page-title', 'Tambah Collection')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.collection.store') }}" method="POST">
      @csrf
      <div class="mb-3">
         <label for="collection_name" class="form-label fw-semibold">Nama Collection <span class="text-danger">*</span></label>
         <input type="text" name="collection_name" id="collection_name" class="form-control rounded-3" value="{{ old('collection_name') }}" required>
      </div>

      <div class="mb-3">
         <label for="merek_id" class="form-label fw-semibold">Merek / Brand</label>
         <select name="merek_id" id="merek_id" class="form-select rounded-3">
            <option value="">-- Pilih Merek --</option>
            @foreach($mereks as $merek)
               <option value="{{ $merek->id }}" {{ old('merek_id') == $merek->id ? 'selected' : '' }}>{{ $merek->nama_merek }}</option>
            @endforeach
         </select>
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug (Opsional)</label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug') }}">
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Collection</button>
         <a href="{{ route('admin.collection.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
