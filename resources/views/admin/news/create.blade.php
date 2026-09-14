@extends('layouts.admin')

@section('title', 'Tambah Berita')
@section('page-title', 'Tambah Berita')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <form action="{{ route('admin.news.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="mb-3">
         <label for="judul" class="form-label fw-semibold">Judul Berita (Indonesia) <span class="text-danger">*</span></label>
         <input type="text" name="judul" id="judul" class="form-control rounded-3" value="{{ old('judul') }}" required>
      </div>

      <div class="mb-3">
         <label for="judul_eng" class="form-label fw-semibold">Judul Berita (English)</label>
         <input type="text" name="judul_eng" id="judul_eng" class="form-control rounded-3" value="{{ old('judul_eng') }}">
      </div>

      <div class="mb-3">
         <label for="slug" class="form-label fw-semibold">Slug (Opsional)</label>
         <input type="text" name="slug" id="slug" class="form-control rounded-3" value="{{ old('slug') }}">
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="tanggal" class="form-label fw-semibold">Tanggal Terbit (ID)</label>
            <input type="text" name="tanggal" id="tanggal" class="form-control rounded-3" value="{{ old('tanggal') }}" placeholder="May 20, 2026">
         </div>
         <div class="col-md-6">
            <label for="tanggal_eng" class="form-label fw-semibold">Tanggal Terbit (EN)</label>
            <input type="text" name="tanggal_eng" id="tanggal_eng" class="form-control rounded-3" value="{{ old('tanggal_eng') }}" placeholder="May 20, 2026">
         </div>
      </div>

      <div class="mb-3">
         <label for="image" class="form-label fw-semibold">Upload Gambar Berita</label>
         <input type="file" name="image" id="image" class="form-control rounded-3">
      </div>

      <div class="mb-3">
         <label for="content" class="form-label fw-semibold">Isi Konten Berita (Indonesia)</label>
         <textarea name="content" id="content" rows="6" class="form-control rounded-3">{{ old('content') }}</textarea>
      </div>

      <div class="mb-4">
         <label for="content_eng" class="form-label fw-semibold">Isi Konten Berita (English)</label>
         <textarea name="content_eng" id="content_eng" rows="6" class="form-control rounded-3">{{ old('content_eng') }}</textarea>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Simpan Berita</button>
         <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
