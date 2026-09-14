@extends('layouts.admin')

@section('title', 'Tambah Variant')
@section('page-title', 'Tambah Variant')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.variant.store') }}" method="POST">
      @csrf
      <div class="mb-3">
         <label for="variant_name" class="form-label fw-semibold">Nama Variant <span class="text-danger">*</span></label>
         <input type="text" name="variant_name" id="variant_name" class="form-control rounded-3" value="{{ old('variant_name') }}" required placeholder="Misal: Sumatra Mandheling, Bali Kintamani">
      </div>

      <div class="mb-3">
         <label for="type_id" class="form-label fw-semibold">Type Produk</label>
         <select name="type_id" id="type_id" class="form-select rounded-3">
            <option value="">-- Pilih Type --</option>
            @foreach($types as $type)
               <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>{{ $type->type_name }}</option>
            @endforeach
         </select>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="taste" class="form-label fw-semibold">Taste Profile</label>
            <input type="text" name="taste" id="taste" class="form-control rounded-3" value="{{ old('taste') }}" placeholder="Chocolate, Herbal, Fruity">
         </div>
         <div class="col-md-6">
            <label for="roast" class="form-label fw-semibold">Roast Level</label>
            <input type="text" name="roast" id="roast" class="form-control rounded-3" value="{{ old('roast') }}" placeholder="Medium Dark Roast">
         </div>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="acidity" class="form-label fw-semibold">Acidity (0 - 5)</label>
            <input type="number" step="0.1" name="acidity" id="acidity" class="form-control rounded-3" value="{{ old('acidity') }}">
         </div>
         <div class="col-md-6">
            <label for="body" class="form-label fw-semibold">Body (0 - 5)</label>
            <input type="number" step="0.1" name="body" id="body" class="form-control rounded-3" value="{{ old('body') }}">
         </div>
      </div>

      <div class="mb-3">
         <label for="description" class="form-label fw-semibold">Deskripsi / Story</label>
         <textarea name="description" id="description" rows="3" class="form-control rounded-3">{{ old('description') }}</textarea>
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Variant</button>
         <a href="{{ route('admin.variant.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
