@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')

@section('content')
<div class="card border-0 shadow-sm rounded-4 bg-body p-4">
   <form action="{{ route('admin.produk.update', $produk->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      
      <div class="row g-3 mb-3">
         <div class="col-md-8">
            <label for="nama_produk" class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
            <input type="text" name="nama_produk" id="nama_produk" class="form-control rounded-3" value="{{ old('nama_produk', $produk->nama_produk) }}" required>
         </div>
         <div class="col-md-4">
            <label for="sku" class="form-label fw-semibold">SKU Produk</label>
            <input type="text" name="sku" id="sku" class="form-control rounded-3" value="{{ old('sku', $produk->sku) }}">
         </div>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-4">
            <label for="merek_id" class="form-label fw-semibold">Merek</label>
            <select name="merek_id" id="merek_id" class="form-select rounded-3">
               <option value="">-- Pilih Merek --</option>
               @foreach($mereks as $merek)
                  <option value="{{ $merek->id }}" {{ old('merek_id', $produk->merek_id) == $merek->id ? 'selected' : '' }}>{{ $merek->nama_merek }}</option>
               @endforeach
            </select>
         </div>
         <div class="col-md-4">
            <label for="kategori_id" class="form-label fw-semibold">Kategori</label>
            <select name="kategori_id" id="kategori_id" class="form-select rounded-3">
               <option value="">-- Pilih Kategori --</option>
               @foreach($kategoris as $kategori)
                  <option value="{{ $kategori->id }}" {{ old('kategori_id', $produk->kategori_id) == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama_kategori }}</option>
               @endforeach
            </select>
         </div>
         <div class="col-md-4">
            <label for="collection_id" class="form-label fw-semibold">Collection</label>
            <select name="collection_id" id="collection_id" class="form-select rounded-3">
               <option value="">-- Pilih Collection --</option>
               @foreach($collections as $col)
                  <option value="{{ $col->id }}" {{ old('collection_id', $produk->collection_id) == $col->id ? 'selected' : '' }}>{{ $col->collection_name }}</option>
               @endforeach
            </select>
         </div>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-6">
            <label for="type_id" class="form-label fw-semibold">Type Produk</label>
            <select name="type_id" id="type_id" class="form-select rounded-3">
               <option value="">-- Pilih Type --</option>
               @foreach($types as $t)
                  <option value="{{ $t->id }}" {{ old('type_id', $produk->type_id) == $t->id ? 'selected' : '' }}>{{ $t->type_name }}</option>
               @endforeach
            </select>
         </div>
         <div class="col-md-6">
            <label for="variant_id" class="form-label fw-semibold">Variant Produk</label>
            <select name="variant_id" id="variant_id" class="form-select rounded-3">
               <option value="">-- Pilih Variant --</option>
               @foreach($variants as $v)
                  <option value="{{ $v->id }}" {{ old('variant_id', $produk->variant_id) == $v->id ? 'selected' : '' }}>{{ $v->variant_name }}</option>
               @endforeach
            </select>
         </div>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-4">
            <label for="harga_reguler" class="form-label fw-semibold">Harga Reguler (Rp)</label>
            <input type="number" step="100" name="harga_reguler" id="harga_reguler" class="form-control rounded-3" value="{{ old('harga_reguler', $produk->harga_reguler) }}">
         </div>
         <div class="col-md-4">
            <label for="tipe_packing" class="form-label fw-semibold">Tipe Packing</label>
            <input type="text" name="tipe_packing" id="tipe_packing" class="form-control rounded-3" value="{{ old('tipe_packing', $produk->tipe_packing) }}">
         </div>
         <div class="col-md-4">
            <label for="inner_kemasan" class="form-label fw-semibold">Inner Kemasan</label>
            <input type="text" name="inner_kemasan" id="inner_kemasan" class="form-control rounded-3" value="{{ old('inner_kemasan', $produk->inner_kemasan) }}">
         </div>
      </div>

      <div class="mb-3">
         <label for="gambar_utama_file" class="form-label fw-semibold">Ganti Gambar Utama Produk</label>
         @if($produk->gambar_utama)
            <div class="mb-2">
               <img src="{{ asset($produk->gambar_utama) }}" alt="{{ $produk->nama_produk }}" style="height: 60px; object-fit: contain;">
            </div>
         @endif
         <input type="file" name="gambar_utama_file" id="gambar_utama_file" class="form-control rounded-3">
      </div>

      <div class="mb-3">
         <label for="deskripsi_singkat" class="form-label fw-semibold">Deskripsi Singkat</label>
         <textarea name="deskripsi_singkat" id="deskripsi_singkat" rows="2" class="form-control rounded-3">{{ old('deskripsi_singkat', $produk->deskripsi_singkat) }}</textarea>
      </div>

      <div class="mb-3">
         <label for="deskripsi_lengkap" class="form-label fw-semibold">Deskripsi Lengkap</label>
         <textarea name="deskripsi_lengkap" id="deskripsi_lengkap" rows="4" class="form-control rounded-3">{{ old('deskripsi_lengkap', $produk->deskripsi_lengkap) }}</textarea>
      </div>

      <div class="row g-3 mb-3">
         <div class="col-md-4">
            <label for="link_tokopedia" class="form-label fw-semibold">Link Tokopedia</label>
            <input type="url" name="link_tokopedia" id="link_tokopedia" class="form-control rounded-3" value="{{ old('link_tokopedia', $produk->link_tokopedia) }}">
         </div>
         <div class="col-md-4">
            <label for="link_shopee" class="form-label fw-semibold">Link Shopee</label>
            <input type="url" name="link_shopee" id="link_shopee" class="form-control rounded-3" value="{{ old('link_shopee', $produk->link_shopee) }}">
         </div>
         <div class="col-md-4">
            <label for="link_lazada" class="form-label fw-semibold">Link Lazada</label>
            <input type="url" name="link_lazada" id="link_lazada" class="form-control rounded-3" value="{{ old('link_lazada', $produk->link_lazada) }}">
         </div>
      </div>

      <div class="form-check mb-3">
         <input type="checkbox" name="is_unggulan" value="1" id="is_unggulan" class="form-check-input" {{ old('is_unggulan', $produk->is_unggulan) ? 'checked' : '' }}>
         <label for="is_unggulan" class="form-check-label fw-semibold">Tampilkan sebagai Produk Unggulan</label>
      </div>

      <div class="mb-4">
         <label for="status" class="form-label fw-semibold">Status</label>
         <select name="status" id="status" class="form-select rounded-3">
            <option value="active" {{ old('status', $produk->status) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $produk->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
         </select>
      </div>

      <div class="d-flex gap-2">
         <button type="submit" class="btn btn-custom-1 rounded-pill px-4">Update Produk</button>
         <a href="{{ route('admin.produk.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
      </div>
   </form>
</div>
@endsection
