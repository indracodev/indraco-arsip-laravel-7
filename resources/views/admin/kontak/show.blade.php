@extends('layouts.admin')

@section('title', 'Detail Pesan Kontak')
@section('page-title', 'Detail Pesan Kontak')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4 max-w-2xl mx-auto">
   <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
      <div>
         <h2 class="fs-4 fw-semibold mb-1">{{ $kontak->nama }}</h2>
         <span class="small text-secondary">{{ $kontak->email }} • {{ $kontak->telepon ?? 'Tanpa No. Telp' }}</span>
      </div>
      <span class="small text-secondary">{{ $kontak->tanggal_kirim ?? $kontak->created_at }}</span>
   </div>

   @if($kontak->judul_pesan)
      <div class="mb-3">
         <span class="small text-secondary fw-semibold">SUBJEK / JUDUL:</span>
         <div class="fs-5 fw-semibold text-custom-2 mt-1">{{ $kontak->judul_pesan }}</div>
      </div>
   @endif

   <div class="mb-4">
      <span class="small text-secondary fw-semibold">ISI PESAN:</span>
      <div class="p-3 bg-body-tertiary rounded-3 mt-2 lh-base">
         {!! nl2br(e($kontak->pesan)) !!}
      </div>
   </div>

   <div class="d-flex gap-2">
      <a href="mailto:{{ $kontak->email }}" class="btn btn-custom-1 rounded-pill px-4">Balas Email</a>
      <a href="{{ route('admin.kontak.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Kembali</a>
   </div>
</div>
@endsection
