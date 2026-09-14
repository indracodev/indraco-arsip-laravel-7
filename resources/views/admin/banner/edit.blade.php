@extends('layouts.admin')

@section('title', 'Edit Banner')
@section('page-title', 'Edit Banner')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.banner.update', $banner->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="mb-3">
         <label for="image" class="form-label fw-semibold">Ganti Gambar Banner</label>
         @if($banner->image_path)
            <div class="mb-2">
               <img src="{{ asset($banner->image_path) }}" alt="Banner" style="height: 60px; object-fit: cover;" class="rounded">
            </div>
         @endif
         <input type="file" name="image" id="image" class="form-control rounded-3">
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="title_id" class="form-label fw-semibold">Judul (ID)</label>
            <input type="text" name="title_id" id="title_id" class="form-control rounded-3" value="{{ old('title_id', $banner->title_id) }}">
         </div>
         <div class="col-md-6">
            <label for="title_en" class="form-label fw-semibold">Judul (EN)</label>
            <input type="text" name="title_en" id="title_en" class="form-control rounded-3" value="{{ old('title_en', $banner->title_en) }}">
         </div>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="subtitle_id" class="form-label fw-semibold">Sub-judul (ID)</label>
            <input type="text" name="subtitle_id" id="subtitle_id" class="form-control rounded-3" value="{{ old('subtitle_id', $banner->subtitle_id) }}">
         </div>
         <div class="col-md-6">
            <label for="subtitle_en" class="form-label fw-semibold">Sub-judul (EN)</label>
            <input type="text" name="subtitle_en" id="subtitle_en" class="form-control rounded-3" value="{{ old('subtitle_en', $banner->subtitle_en) }}">
         </div>
      </div>

      <div class="mb-3">
         <label for="link" class="form-label fw-semibold">URL Destination Link</label>
         <input type="text" name="link" id="link" class="form-control rounded-3" value="{{ old('link', $banner->link) }}">
      </div>

      <div class="mb-3">
         <label for="order_num" class="form-label fw-semibold">Urutan Banner</label>
         <input type="number" name="order_num" id="order_num" class="form-control rounded-3" value="{{ old('order_num', $banner->order_num) }}">
      </div>

      <div class="form-check mb-4">
         <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
         <label for="is_active" class="form-check-label fw-semibold">Banner Aktif</label>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Update Banner</button>
         <a href="{{ route('admin.banner.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
