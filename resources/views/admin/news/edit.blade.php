@extends('layouts.admin')

@section('title', 'Edit Berita')
@section('page-title', 'Edit Berita')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.news.update', $news->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="mb-3">
         <label for="judul" class="form-label fw-semibold">Judul Berita (Indonesia) <span class="text-danger">*</span></label>
         <input type="text" name="judul" id="judul" class="form-control rounded-3" value="{{ old('judul', $news->judul) }}" required>
      </div>

      <div class="mb-3">
         <label for="judul_eng" class="form-label fw-semibold">Judul Berita (English)</label>
         <input type="text" name="judul_eng" id="judul_eng" class="form-control rounded-3" value="{{ old('judul_eng', $news->judul_eng) }}">
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug', $news->slug) }}" required>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="tanggal" class="form-label fw-semibold">Tanggal Terbit (ID)</label>
            <input type="text" name="tanggal" id="tanggal" class="form-control rounded-3" value="{{ old('tanggal', $news->tanggal) }}">
         </div>
         <div class="col-md-6">
            <label for="tanggal_eng" class="form-label fw-semibold">Tanggal Terbit (EN)</label>
            <input type="text" name="tanggal_eng" id="tanggal_eng" class="form-control rounded-3" value="{{ old('tanggal_eng', $news->tanggal_eng) }}">
         </div>
      </div>

      <div class="mb-3">
         <label for="image" class="form-label fw-semibold">Ganti Gambar Berita</label>
         @if($news->image_path)
            <div class="mb-2">
               <img src="{{ asset($news->image_path) }}" alt="{{ $news->judul }}" style="height: 60px; object-fit: cover;" class="rounded">
            </div>
         @endif
         <input type="file" name="image" id="image" class="form-control rounded-3">
      </div>

      <div class="mb-3">
         <label for="content" class="form-label fw-semibold">Isi Konten Berita (Indonesia)</label>
         <textarea name="content" id="content" rows="6" class="form-control rounded-3">{{ old('content', $news->content) }}</textarea>
      </div>

      <div class="mb-4">
         <label for="content_eng" class="form-label fw-semibold">Isi Konten Berita (English)</label>
         <textarea name="content_eng" id="content_eng" rows="6" class="form-control rounded-3">{{ old('content_eng', $news->content_eng) }}</textarea>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Update Berita</button>
         <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
