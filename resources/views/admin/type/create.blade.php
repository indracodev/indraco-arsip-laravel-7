@extends('layouts.admin')

@section('title', 'Tambah Type')
@section('page-title', 'Tambah Type')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.type.store') }}" method="POST">
      @csrf
      <div class="mb-3">
         <label for="type_name" class="form-label fw-semibold">Nama Type <span class="text-danger">*</span></label>
         <input type="text" name="type_name" id="type_name" class="form-control rounded-3" value="{{ old('type_name') }}" required placeholder="Misal: Capsules, Beans, Ground">
      </div>

      <div class="mb-3">
         <label for="collection_id" class="form-label fw-semibold">Collection (Opsional)</label>
         <select name="collection_id" id="collection_id" class="form-select rounded-3">
            <option value="">-- Pilih Collection --</option>
            @foreach($collections as $collection)
               <option value="{{ $collection->id }}" {{ old('collection_id') == $collection->id ? 'selected' : '' }}>{{ $collection->collection_name }}</option>
            @endforeach
         </select>
      </div>

      <div class="mb-4">
         <label for="slug" class="form-label fw-semibold">Slug (Opsional)</label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug') }}">
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Type</button>
         <a href="{{ route('admin.type.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
