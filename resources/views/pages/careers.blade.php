@extends('layouts.app')

@section('title', 'INDRACO – Careers')

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/home-banner.css') }}">
   <style>
      .btn-custom-1 {
         background-color: var(--custom-primary, #004b49) !important;
         border-color: var(--custom-primary, #004b49) !important;
         color: #ffffff !important;
      }
      .btn-custom-1:hover {
         background-color: #003634 !important;
         border-color: #003634 !important;
         color: #ffffff !important;
      }
      .text-teal { color: var(--custom-primary, #004b49) !important; }
   </style>
@endpush

@section('content')
<main id="content" tabindex="-1">

   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title">CAREERS</h2>
            </div>
            <div class="col col-6 z-2">
               <h3 class="banner-text">Empowering Careers Through Innovation and Collaboration.</h3>
            </div>
            <div class="col col-6 col-xxl-5 ms-auto z-1">
               <div class="banner-media">
                  <div class="banner-images-wrapper">
                     <img src="{{ asset('images/icon-career.png') }}" alt="Careers" class="banner-images">
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

   <!-- Section Job Vacancy Out Portals -->
   <section aria-label="careers out section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <h2 class="fs-3 fw-semibold mb-4 text-teal">JOB VACANCY PORTALS</h2>
         <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 g-xl-4">
            <div class="col">
               <a href="https://id.jobstreet.com/id/companies/indraco-168551422470741" target="_blank" class="text-reset text-decoration-none">
                  <article class="ratio ratio-16x9 rounded-4 card p-4 shadow overflow-hidden">
                     <img src="{{ asset('images/JobS.png') }}" data-light="{{ asset('images/JobS.png') }}" data-dark="{{ asset('images/JobS-invert.png') }}" alt="JobStreet INDRACO" loading="lazy" class="theme-image object-fit-contain w-75 h-75 top-50 start-50 translate-middle">
                  </article>
               </a>
            </div>
            <div class="col">
               <a href="https://www.linkedin.com/company/indraco-group/posts/?feedView=all" target="_blank" class="text-reset text-decoration-none">
                  <article class="ratio ratio-16x9 rounded-4 card p-4 shadow overflow-hidden">
                     <img src="{{ asset('images/LinkedIn.png') }}" data-light="{{ asset('images/LinkedIn.png') }}" data-dark="{{ asset('images/LinkedIn-invert.png') }}" alt="LinkedIn INDRACO" loading="lazy" class="theme-image object-fit-contain w-75 h-75 top-50 start-50 translate-middle">
                  </article>
               </a>
            </div>
         </div>
      </div>
   </section>

   <!-- Section Job Vacancies List -->
   <section aria-label="careers header section" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <h2 class="fs-3 fw-semibold mb-4 text-teal">FEATURED OPEN POSITIONS</h2>
         <ul class="list-unstyled row brochure-list row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-0">
            <li class="brochure-item">
               <div class="card careers-card rounded-4 overflow-hidden shadow d-flex flex-column h-100 p-4 border-0">
                  <h3 class="card-title fs-5 flex-grow-1 mb-3 text-teal">Area Sales &amp; Promotion Supervisor</h3>
                  <p class="card-text mb-0 d-flex align-items-center gap-2 text-muted">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>
                     <small>Surabaya, Jawa Timur</small>
                  </p>
                  <a href="#form-careers" class="stretched-link"></a>
               </div>
            </li>
            <li class="brochure-item">
               <div class="card careers-card rounded-4 overflow-hidden shadow d-flex flex-column h-100 p-4 border-0">
                  <h3 class="card-title fs-5 flex-grow-1 mb-3 text-teal">Brand Executive &amp; Digital Specialist</h3>
                  <p class="card-text mb-0 d-flex align-items-center gap-2 text-muted">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>
                     <small>Jakarta Selatan, DKI Jakarta</small>
                  </p>
                  <a href="#form-careers" class="stretched-link"></a>
               </div>
            </li>
            <li class="brochure-item">
               <div class="card careers-card rounded-4 overflow-hidden shadow d-flex flex-column h-100 p-4 border-0">
                  <h3 class="card-title fs-5 flex-grow-1 mb-3 text-teal">Human Capital Services Manager</h3>
                  <p class="card-text mb-0 d-flex align-items-center gap-2 text-muted">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="16" height="16"><path fill="currentColor" d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>
                     <small>Sidoarjo, Jawa Timur</small>
                  </p>
                  <a href="#form-careers" class="stretched-link"></a>
               </div>
            </li>
         </ul>
      </div>
   </section>

   <!-- Form Careers (Talent Network) -->
   <section id="form-careers" aria-label="form careers" class="container mb-5">
      <div class="bg-body-secondary px-4 py-5 p-lg-5 rounded-4 overflow-hidden">
         <h2 class="fs-3 fw-semibold mb-4 text-teal">Bergabunglah Dengan Jaringan Talenta Kami</h2>
         
         @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
               <strong>✅ Berhasil!</strong> {{ session('success') }}
               <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
         @endif

         <form action="{{ route('contact.store') }}" method="POST" enctype="multipart/form-data" class="mb-5">
            @csrf
            <input type="hidden" name="judul_pesan" value="Pendaftaran Jaringan Talenta / Lamaran Pekerjaan">
            <div class="row g-3 gx-xl-4">
               <div class="form-group col col-12 col-md-6 col-lg-4">
                  <label for="nama_depan" class="form-label fw-medium">Nama Depan *</label>
                  <input type="text" id="nama_depan" name="nama_depan" required class="form-control rounded-pill bg-body-tertiary border">
               </div>
               <div class="form-group col col-12 col-md-6 col-lg-4">
                  <label for="nama_belakang" class="form-label fw-medium">Nama Belakang *</label>
                  <input type="text" id="nama_belakang" name="nama_belakang" required class="form-control rounded-pill bg-body-tertiary border">
               </div>
               <div class="form-group col col-12 col-md-6 col-lg-4">
                  <label for="email" class="form-label fw-medium">Alamat Email *</label>
                  <input type="email" id="email" name="email" required class="form-control rounded-pill bg-body-tertiary border">
               </div>
               <div class="form-group col col-12 col-md-6">
                  <div class="row g-2">
                     <div class="col col-auto">
                        <label for="kode_negara" class="form-label fw-medium">Kode *</label>
                        <select name="kode_negara" id="kode_negara" class="form-select rounded-pill bg-body-tertiary border">
                           <option value="+62">+62 (ID)</option>
                           <option value="+1">+1 (US)</option>
                           <option value="+65">+65 (SG)</option>
                        </select>
                     </div>
                     <div class="col">
                        <label for="nomor_telepon" class="form-label fw-medium">Nomor Telepon *</label>
                        <input type="text" id="nomor_telepon" name="nomor_telepon" required class="form-control rounded-pill bg-body-tertiary border">
                     </div>
                  </div>
               </div>
               <div class="form-group col col-12 col-md-6">
                  <label for="berkas" class="form-label fw-medium">Unggah Resume / CV *</label>
                  <input type="file" id="berkas" name="berkas" class="form-control rounded-pill bg-body-tertiary border">
               </div>
            </div>
            <hr class="my-4">
            <p class="text-muted small">
               Pilih kategori pekerjaan dari daftar pilihan. Cari lokasi dan pilih salah satu dari daftar saran. Terakhir, klik “Tambahˮ untuk membuat pemberitahuan lowongan kerja Anda.
            </p>
            <div class="row g-3 gx-xl-4">
               <div class="form-group col col-12 col-lg">
                  <label for="kategori_pekerjaan" class="form-label fw-medium">Kategori Pekerjaan</label>
                  <select name="kategori_pekerjaan" id="kategori_pekerjaan" class="form-select rounded-pill bg-body-tertiary border">
                     <option value="General" selected>Pilih Kategori Pekerjaan</option>
                     <option value="Sales & Marketing">Sales &amp; Marketing</option>
                     <option value="Brand & Digital">Brand &amp; Digital Specialist</option>
                     <option value="Human Capital & HR">Human Capital &amp; HR</option>
                     <option value="Operations & Supply Chain">Operations &amp; Supply Chain</option>
                     <option value="IT & Software Development">IT &amp; Software Development</option>
                  </select>
               </div>
               <div class="form-group col col-lg-7">
                  <label for="location" class="form-label fw-medium">Lokasi Preferensi</label>
                  <div class="input-group rounded-pill overflow-hidden border">
                     <input type="text" id="location" name="lokasi" placeholder="Misal: Surabaya, Sidoarjo, Jakarta" class="form-control bg-body-tertiary border-0 px-3">
                     <button type="button" class="btn btn-custom-1 rounded-0 px-4">Tambahkan</button>
                  </div>
               </div>
            </div>
            <div class="form-group mt-3">
               <label for="pesan" class="form-label fw-medium">Catatan / Ringkasan Pengalaman *</label>
               <textarea id="pesan" name="pesan" rows="3" required placeholder="Tuliskan ringkasan pengalaman singkat atau posisi yang diminati..." class="form-control rounded-4 bg-body-tertiary border"></textarea>
            </div>
            <hr class="my-4">
            <p class="text-muted small">
               Dengan mendaftar, saya menyatakan bahwa saya telah membaca pemberitahuan privasi PT. INDRACO Jaya Perkasa, dan bahwa saya ingin menerima komunikasi melalui email dan SMS. Saya memahami bahwa saya dapat berhenti menerima komunikasi email dan SMS kapan saja.
            </p>
            <div class="row row-cols-auto g-3 gx-lg-4 align-items-center">
               <div class="col"><button type="submit" class="btn btn-lg btn-custom-1 rounded-pill px-5 fw-semibold">Daftar</button></div>
               <div class="col text-muted small">Sudah punya akun? <a href="{{ route('admin.login') }}" class="fw-semibold text-teal text-decoration-none">Masuk</a></div>
            </div>
         </form>

         <!-- Fraud Warning Section -->
         <div class="d-flex align-items-start gap-3 p-3 bg-body-tertiary rounded-4 border">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="none" aria-hidden="true" width="36" height="36" class="text-warning flex-shrink-0"><path fill="currentColor" d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
            <p class="small text-muted mb-0">
               <strong>PERINGATAN PENIPUAN DAN KECURANGAN REKRUTMEN.</strong> PT. INDRACO Jaya Perkasa tidak pernah meminta pembayaran atau detail finansial dalam seluruh proses rekrutmen. Harap waspada terhadap aktivitas email atau komunikasi mencurigakan dari pihak yang berpura-pura atas nama perekrut atau manajemen INDRACO. Jika ragu, abaikan pesan tersebut.
            </p>
         </div>
      </div>
   </section>

</main>
@endsection
