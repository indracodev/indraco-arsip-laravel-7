@extends('layouts.app')

@section('title', 'INDRACO – Contact Us')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <style>
      @media (min-width: 992px) {
         .btn-submit { max-width: 210px; }
      }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1">

   <!-- Banner Section -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">CONTACT US</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Let's Talk About Your Business Needs.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-contact.png') }}" alt="Contact Us Banner Icon" class="banner-images">
                  </div>
                  <div class="pedestal z-0">
                     <div class="pedestal-wrapper">
                        <div class="pedestal-top"></div>
                        <div class="pedestal-body"></div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col col-12 z-3">
               <div class="banner-sosmed d-flex justify-content-center justify-content-lg-end">
                  @include('components.sosmed')
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Address Section -->
   <section aria-label="address section" class="container mb-5">
      <address class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 font-normal">
         <h2 class="fs-3 fw-semibold mb-4">CONTACT US</h2>
         <div class="row gy-4 gx-xl-5 justify-content-between">
            <div class="col col-12 col-md-6 col-lg-4">
               <h3 class="fs-5 fw-semibold">PT. Indraco Global Indonesia</h3>
               <p class="mb-0">
                  Jl. Semeru No. 133-135 Bambe, Kec. Driyorejo. Gresik 61177 Jawa Timur - Indonesia
                  <br>
                  <b>T</b>. +62 31 766 8777, 766 7388
                  <br>
                  <b>F</b>. +62 31 766 9590
                  <br><br>
                  <b>E</b>. info@indraco.com
                  <br>
                  www.indraco.com
               </p>
            </div>
            <div class="col col-12 col-md-6 col-lg-auto">
               <h3 class="fs-5 fw-semibold">Sales Contacts Domestic</h3>
               <ul class="ps-3 mb-0">
                  <li><b>General Trade</b> : <br> getra@indraco.com</li>
                  <li><b>Modern Trade</b> : <br> motra@indraco.com</li>
                  <li><b>eCommerce</b> : <br> ecom@indraco.com</li>
                  <li><b>Foodservice</b> : <br> fopro@indraco.com</li>
                  <li><b>F&amp;B Services</b> : <br> fobev@indraco.com</li>
               </ul>
            </div>
            <div class="col col-12 col-md-6 col-lg-auto">
               <h3 class="fs-5 fw-semibold">International</h3>
               <ul class="ps-3 mb-0">
                  <li><b>International</b> : <br> inbus@indraco.com</li>
               </ul>
            </div>
         </div>
      </address>
   </section>

   <!-- Contact Form Section -->
   <section aria-label="contact section" class="container mb-5">
      <h2 class="fs-3 fw-semibold mb-4">Formulir Kontak</h2>
      <p class="mb-5">
         Silakan gunakan formulir di bawah ini untuk menghubungi PT. Indraco Global Indonesia dan kami akan menghubungi Anda sesegera mungkin.
         <br><br>
         Jika pertanyaan Anda terkait dengan pekerjaan, silakan kunjungi pusat karier kami, atau gunakan pemilih lokasi untuk menemukan situs web yang paling relevan dengan lokasi Anda. Jika lokasi Anda tidak memiliki situs web lokal, silakan gunakan formulir kontak umum di bawah ini.
      </p>
      <hr class="mt-2 mb-4">

      @if(session('success'))
         <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      <form action="{{ route('contact.store') }}" method="POST" aria-label="contact form">
         @csrf
         <div class="row g-3 row-cols-1 row-cols-lg-2 gx-lg-5 mb-5">
            <div class="col">
               <div class="row g-3">
                  <div class="form-group col col-12 col-sm-6">
                     <label for="nama_depan" class="form-label">Nama Depan *</label>
                     <input type="text" name="nama_depan" id="nama_depan" class="form-control bg-body-tertiary rounded-pill" required>
                  </div>
                  <div class="form-group col col-12 col-sm-6">
                     <label for="nama_belakang" class="form-label">Nama Belakang *</label>
                     <input type="text" name="nama_belakang" id="nama_belakang" class="form-control bg-body-tertiary rounded-pill">
                  </div>
                  <div class="form-group col col-12">
                     <label for="email" class="form-label">Alamat Email *</label>
                     <input type="email" name="email" id="email" class="form-control bg-body-tertiary rounded-pill" required>
                  </div>
                  <div class="form-group col col-12">
                     <div class="row g-2">
                        <div class="col col-auto">
                           <label for="kode_negara" class="form-label">Kode Negara *</label>
                           <select name="kode_negara" id="kode_negara" class="form-select rounded-pill bg-body-tertiary border">
                              <option value="+62">+62</option>
                              <option value="+60">+60</option>
                              <option value="+65">+65</option>
                              <option value="+1">+1</option>
                              <option value="+86">+86</option>
                           </select>
                        </div>
                        <div class="col">
                           <label for="nomor_telepon" class="form-label">Nomor Telepon *</label>
                           <input type="text" name="nomor_telepon" id="nomor_telepon" class="form-control rounded-pill bg-body-tertiary border" required>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col form-group d-flex flex-column">
               <label for="pesan" class="form-label">Pesan Anda *</label>
               <div class="flex-grow-1">
                  <textarea name="pesan" id="pesan" class="form-control bg-body-tertiary flex-grow-1 h-100" rows="5" style="border-radius: 18.8px;" required></textarea>
               </div>
            </div>
         </div>
         <div class="d-flex flex-column flex-lg-row row-gap-4 column-gap-lg-4 align-items-center">
            <div class="form-check">
               <input class="form-check-input" type="radio" name="approval" id="approval-contact" required checked>
               <label class="form-check-label" for="approval-contact">
                  Ya - saya menyatakan bahwa saya berusia di atas 16 tahun. Dengan mengirimkan formulir ini, Anda menyetujui Syarat dan Ketentuan dan telah membaca pemberitahuan privasi*.
               </label>
            </div>
            <button type="submit" class="btn btn-lg btn-custom-1 rounded-pill w-100 btn-submit">KIRIM</button>
         </div>
      </form>
   </section>

</main>
@endsection
