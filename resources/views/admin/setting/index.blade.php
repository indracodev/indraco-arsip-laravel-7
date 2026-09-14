@extends('layouts.app')

@section('title', 'INDRACO – Pengaturan Website & Konten Halaman')

@push('styles')
   <style>
      .admin-sidebar .nav-link {
         color: var(--bs-body-color);
         border-radius: 12px;
         padding: 10px 14px;
         font-weight: 500;
         font-size: 0.88rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
         transition: all 0.2s ease;
         margin-bottom: 3px;
         text-decoration: none;
      }
      .admin-sidebar .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.1);
         color: var(--custom-primary);
      }
      .admin-sidebar .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .admin-sidebar-title {
         font-size: 0.72rem;
         text-transform: uppercase;
         letter-spacing: 0.08em;
         opacity: 0.6;
         font-weight: 700;
         margin-top: 1.2rem;
         margin-bottom: 0.4rem;
         padding-left: 8px;
      }
      .sticky-sidebar {
         position: sticky;
         top: 90px;
      }
      .logo-preview-box {
         background-color: var(--bs-body-bg);
         border: 2px dashed rgba(var(--custom-primary-rgb), 0.3);
         border-radius: 16px;
         padding: 20px;
         text-align: center;
         transition: all 0.3s ease;
      }
      .logo-preview-box:hover {
         border-color: var(--custom-primary);
      }
      .sosmed-item-card {
         background-color: var(--bs-body-bg);
         border-radius: 14px;
         padding: 16px;
         border: 1px solid rgba(128,128,128,0.15);
         box-shadow: 0 2px 6px rgba(0,0,0,0.03);
      }
      .setting-tab-slider {
         overflow-x: auto;
         white-space: nowrap;
         -webkit-overflow-scrolling: touch;
         scrollbar-width: none;
      }
      .setting-tab-slider::-webkit-scrollbar {
         display: none;
      }
      #settingTab .nav-link {
         color: var(--bs-body-color);
         background-color: transparent;
         border-radius: 20px;
         padding: 8px 16px;
         font-size: 0.86rem;
         font-weight: 600;
         transition: all 0.2s ease;
         white-space: nowrap;
      }
      #settingTab .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.12);
         color: var(--custom-primary);
      }
      #settingTab .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
         box-shadow: 0 4px 12px rgba(var(--custom-primary-rgb), 0.25);
      }
      [data-bs-theme="dark"] #settingTab .nav-link:hover {
         background-color: rgba(var(--custom-primary-rgb), 0.2);
         color: var(--custom-primary);
      }
      [data-bs-theme="dark"] #settingTab .nav-link.active {
         background-color: var(--custom-primary) !important;
         color: #ffffff !important;
      }
      .color-preset-card {
         transition: all 0.2s ease;
         border: 2px solid rgba(128,128,128,0.2) !important;
      }
      .color-preset-card:hover {
         transform: translateY(-3px);
         box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      }
      .color-preset-card.active-preset {
         border-color: var(--custom-primary) !important;
         box-shadow: 0 0 0 3px rgba(var(--custom-primary-rgb), 0.25) !important;
      }
   </style>
@endpush

@push('scripts')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Auto-activate tab based on URL hash (e.g. #tab-about)
         const hash = window.location.hash;
         if (hash) {
            const triggerEl = document.querySelector(`#settingTab button[data-bs-target="${hash}"]`);
            if (triggerEl) {
               const tab = new bootstrap.Tab(triggerEl);
               tab.show();
            }
         }

         // Keep hash updated on tab change
         const tabEls = document.querySelectorAll('#settingTab button[data-bs-toggle="pill"]');
         tabEls.forEach(el => {
            el.addEventListener('shown.bs.tab', function(event) {
               const targetHash = event.target.getAttribute('data-bs-target');
               if (targetHash) {
                  history.replaceState(null, null, targetHash);
               }
            });
         });

         // Theme Color Live Picker & Presets
         window.selectPresetColor = function(hex) {
            const picker = document.getElementById('inputColorPicker');
            const hexInput = document.getElementById('inputHexCode');
            if (picker) picker.value = hex;
            if (hexInput) hexInput.value = hex;
            updateLivePreview(hex);
            highlightActiveCard(hex);
         };

         function updateLivePreview(hex) {
            const badgeCurrent = document.getElementById('badgeCurrentColor');
            const btnPrimary = document.getElementById('previewBtnPrimary');
            const btnOutline = document.getElementById('previewBtnOutline');
            const badge = document.getElementById('previewBadge');
            const text = document.getElementById('previewText');

            if (badgeCurrent) badgeCurrent.innerText = 'Warna Aktif: ' + hex;
            if (btnPrimary) {
               btnPrimary.style.setProperty('background-color', hex, 'important');
               btnPrimary.style.setProperty('border-color', hex, 'important');
            }
            if (btnOutline) {
               btnOutline.style.setProperty('border-color', hex, 'important');
               btnOutline.style.setProperty('color', hex, 'important');
            }
            if (badge) {
               badge.style.setProperty('background-color', hex, 'important');
               badge.style.setProperty('border-color', hex, 'important');
            }
            if (text) {
               text.style.setProperty('color', hex, 'important');
            }
         }

         function highlightActiveCard(hex) {
            document.querySelectorAll('.color-preset-card').forEach(card => {
               if (card.getAttribute('data-color') === hex.toLowerCase()) {
                  card.classList.add('active-preset');
               } else {
                  card.classList.remove('active-preset');
               }
            });
         }

         document.getElementById('inputColorPicker')?.addEventListener('input', function(e) {
            const hex = e.target.value;
            const hexInput = document.getElementById('inputHexCode');
            if (hexInput) hexInput.value = hex;
            updateLivePreview(hex);
            highlightActiveCard(hex);
         });

         document.getElementById('inputHexCode')?.addEventListener('input', function(e) {
            const hex = e.target.value;
            if (/^#[a-fA-F0-9]{6}$/.test(hex)) {
               const picker = document.getElementById('inputColorPicker');
               if (picker) picker.value = hex;
               updateLivePreview(hex);
               highlightActiveCard(hex);
            }
         });
      });
   </script>
@endpush

@section('content')
<main id="content" tabindex="-1" class="container py-4 my-3">

   @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
         <strong>✅ Berhasil!</strong> {{ session('success') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
         <strong>❌ Gagal!</strong> {{ session('error') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
   @endif

   <!-- Header Banner -->
   <section aria-label="Welcome Admin" class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm mb-4">
      <div class="row align-items-center gy-3">
         <div class="col-12 col-md">
            <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill fw-bold" style="background-color: #ee5d1d !important; color: #fff !important;">⚙️ Pengaturan CMS Website</span>
            <h1 class="h3 fw-bold mb-1">Pengaturan Gambar, Teks &amp; Media Sosial Website</h1>
            <p class="text-secondary small mb-0">Kelola konten teks, gambar section, logo header/footer, serta tautan media sosial secara terpisah di setiap tab halaman.</p>
         </div>
         <div class="col-12 col-md-auto">
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">🌐 Pratinjau Website Utama</a>
         </div>
      </div>
   </section>

   <!-- 2 Columns Layout -->
   <div class="row g-4">
      
      <!-- Sidebar Navigasi -->
      <x-admin-sidebar />

      <!-- Main Content -->
      <div class="col-12 col-lg-9">

         <!-- Horizontal Tab Slider for All Pages -->
         <div class="p-2 rounded-4 bg-body-secondary mb-4 border shadow-sm setting-tab-slider">
            <ul class="nav nav-pills flex-nowrap gap-2" id="settingTab" role="tablist">
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap active" id="btn-tab-general" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="true">
                     ⚙️ Umum &amp; Logo
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-color" data-bs-toggle="pill" data-bs-target="#tab-color" type="button" role="tab" aria-controls="tab-color" aria-selected="false">
                     🎨 Color Palette
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-home" data-bs-toggle="pill" data-bs-target="#tab-home" type="button" role="tab" aria-controls="tab-home" aria-selected="false">
                     🏠 Landing Page / Home
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-about" data-bs-toggle="pill" data-bs-target="#tab-about" type="button" role="tab" aria-controls="tab-about" aria-selected="false">
                     ℹ️ About Us
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-business" data-bs-toggle="pill" data-bs-target="#tab-business" type="button" role="tab" aria-controls="tab-business" aria-selected="false">
                     💼 Business
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-store" data-bs-toggle="pill" data-bs-target="#tab-store" type="button" role="tab" aria-controls="tab-store" aria-selected="false">
                     🛒 Online Store
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-careers" data-bs-toggle="pill" data-bs-target="#tab-careers" type="button" role="tab" aria-controls="tab-careers" aria-selected="false">
                     🎯 Careers
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-contact" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button" role="tab" aria-controls="tab-contact" aria-selected="false">
                     📞 Contact Us
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-privacy" data-bs-toggle="pill" data-bs-target="#tab-privacy" type="button" role="tab" aria-controls="tab-privacy" aria-selected="false">
                     🔒 Privacy Policy
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-terms" data-bs-toggle="pill" data-bs-target="#tab-terms" type="button" role="tab" aria-controls="tab-terms" aria-selected="false">
                     📜 Terms &amp; Conditions
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-dataprotection" data-bs-toggle="pill" data-bs-target="#tab-dataprotection" type="button" role="tab" aria-controls="tab-dataprotection" aria-selected="false">
                     🛡️ Data Protection
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link text-nowrap" id="btn-tab-help" data-bs-toggle="pill" data-bs-target="#tab-help" type="button" role="tab" aria-controls="tab-help" aria-selected="false">
                     ❓ Help / FAQ
                  </button>
               </li>
            </ul>
         </div>

         <!-- TAB CONTENTS -->
         <div class="tab-content" id="settingTabContent">
            
            <!-- ==========================================
                 TAB COLOR PALETTE (CUSTOM THEME COLOR)
            =========================================== -->
            <div class="tab-pane fade" id="tab-color" role="tabpanel" aria-labelledby="btn-tab-color" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                     <div>
                        <h2 class="h5 fw-bold mb-1">🎨 Custom Warna Theme Website</h2>
                        <p class="text-secondary small mb-0">Pilih skema warna dari Color Palette atau tentukan kode HEX kustom untuk menggantikan warna aksen utama (Default: <code>#004b49</code>).</p>
                     </div>
                     <span class="badge bg-custom-1 px-3 py-2 rounded-pill fw-bold" id="badgeCurrentColor">Warna Aktif: {{ $themeColor }}</span>
                  </div>

                  <form action="{{ route('admin.setting.update-theme-color') }}" method="POST" id="formThemeColor">
                     @csrf

                     <!-- Preset Color Palette Options -->
                     <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-secondary mb-3">Pilihan Presets Color Palette:</label>
                        <div class="row g-3 row-cols-2 row-cols-sm-4 row-cols-md-4">
                           
                           <!-- Option 1: Default INDRACO Teal (#004b49) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#004b49' ? 'active-preset' : '' }}" data-color="#004b49" onclick="selectPresetColor('#004b49')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #004b49;"></div>
                                 <span class="fw-bold small d-block mb-0">Teal INDRACO</span>
                                 <code class="small text-muted">#004b49</code>
                              </div>
                           </div>

                           <!-- Option 2: Royal Blue (#0d6efd) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#0d6efd' ? 'active-preset' : '' }}" data-color="#0d6efd" onclick="selectPresetColor('#0d6efd')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #0d6efd;"></div>
                                 <span class="fw-bold small d-block mb-0">Royal Blue</span>
                                 <code class="small text-muted">#0d6efd</code>
                              </div>
                           </div>

                           <!-- Option 3: Indraco Orange (#ee5d1d) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#ee5d1d' ? 'active-preset' : '' }}" data-color="#ee5d1d" onclick="selectPresetColor('#ee5d1d')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #ee5d1d;"></div>
                                 <span class="fw-bold small d-block mb-0">Indraco Orange</span>
                                 <code class="small text-muted">#ee5d1d</code>
                              </div>
                           </div>

                           <!-- Option 4: Emerald Green (#059669) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#059669' ? 'active-preset' : '' }}" data-color="#059669" onclick="selectPresetColor('#059669')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #059669;"></div>
                                 <span class="fw-bold small d-block mb-0">Emerald Green</span>
                                 <code class="small text-muted">#059669</code>
                              </div>
                           </div>

                           <!-- Option 5: Deep Purple (#7c3aed) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#7c3aed' ? 'active-preset' : '' }}" data-color="#7c3aed" onclick="selectPresetColor('#7c3aed')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #7c3aed;"></div>
                                 <span class="fw-bold small d-block mb-0">Deep Purple</span>
                                 <code class="small text-muted">#7c3aed</code>
                              </div>
                           </div>

                           <!-- Option 6: Crimson Red (#dc2626) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#dc2626' ? 'active-preset' : '' }}" data-color="#dc2626" onclick="selectPresetColor('#dc2626')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #dc2626;"></div>
                                 <span class="fw-bold small d-block mb-0">Crimson Red</span>
                                 <code class="small text-muted">#dc2626</code>
                              </div>
                           </div>

                           <!-- Option 7: Dark Slate (#1e293b) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#1e293b' ? 'active-preset' : '' }}" data-color="#1e293b" onclick="selectPresetColor('#1e293b')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #1e293b;"></div>
                                 <span class="fw-bold small d-block mb-0">Dark Slate</span>
                                 <code class="small text-muted">#1e293b</code>
                              </div>
                           </div>

                           <!-- Option 8: Amber Warm (#d97706) -->
                           <div class="col">
                              <div class="color-preset-card p-3 rounded-4 bg-body cursor-pointer text-center position-relative {{ strtolower($themeColor) == '#d97706' ? 'active-preset' : '' }}" data-color="#d97706" onclick="selectPresetColor('#d97706')">
                                 <div class="rounded-circle mx-auto mb-2 shadow-sm" style="width: 42px; height: 42px; background-color: #d97706;"></div>
                                 <span class="fw-bold small d-block mb-0">Amber Warm</span>
                                 <code class="small text-muted">#d97706</code>
                              </div>
                           </div>

                        </div>
                     </div>

                     <!-- Custom Color Picker & Hex Input -->
                     <div class="card border-0 shadow-sm rounded-4 p-4 bg-body mb-4">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary">🎨 Pilihan Warna Custom HEX:</h6>
                        <div class="row align-items-center g-3">
                           <div class="col-auto">
                              <input type="color" class="form-control form-control-color rounded-3 p-1 cursor-pointer" id="inputColorPicker" value="{{ $themeColor }}" title="Pilih warna custom" style="width: 54px; height: 54px;">
                           </div>
                           <div class="col col-md-4">
                              <label class="form-label small fw-semibold mb-1">Kode Warna HEX:</label>
                              <input type="text" name="theme_color" id="inputHexCode" class="form-control rounded-3 fw-bold" value="{{ $themeColor }}" placeholder="#004b49" required pattern="^#[a-fA-F0-9]{6}$">
                           </div>
                           <div class="col-12 col-md-6">
                              <div class="p-3 rounded-3 border bg-body-tertiary">
                                 <span class="small text-secondary d-block">Default Color Website:</span>
                                 <span class="fw-bold text-custom-1">#004b49 (Teal INDRACO Bawaan)</span>
                              </div>
                           </div>
                        </div>
                     </div>

                     <!-- Live Preview Box -->
                     <div class="card border-0 shadow-sm rounded-4 p-4 bg-body mb-4">
                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary">👁️ Pratinjau Elemen Visual Warna:</h6>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                           <button type="button" class="btn btn-custom-1 rounded-pill px-4" id="previewBtnPrimary" style="background-color: {{ $themeColor }} !important; border-color: {{ $themeColor }} !important;">Tombol Utama</button>
                           <button type="button" class="btn btn-custom-1-outline rounded-pill px-4" id="previewBtnOutline" style="border-color: {{ $themeColor }} !important; color: {{ $themeColor }} !important;">Tombol Outline</button>
                           <span class="badge badge-custom-1 px-3 py-2 rounded-pill" id="previewBadge" style="background-color: {{ $themeColor }} !important; border-color: {{ $themeColor }} !important;">Badge Label</span>
                           <span class="fw-bold text-custom-1 fs-5" id="previewText" style="color: {{ $themeColor }} !important;">Teks Aksen Utama</span>
                        </div>
                     </div>

                     <!-- Action Buttons -->
                     <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 py-2 fw-semibold">💾 Simpan Warna Theme Baru</button>
                  </form>

                  <form action="{{ route('admin.setting.reset-theme-color') }}" method="POST" onsubmit="return confirm('Kembalikan warna theme ke default (#004b49)?')">
                     @csrf
                     <button type="submit" class="btn btn-outline-secondary rounded-pill px-4 py-2 small fw-semibold">🔄 Reset ke Default (#004b49)</button>
                  </form>
                     </div>
               </section>
            </div>

            <!-- ==========================================
                 TAB 1: UMUM & LOGO & SOSMED
            =========================================== -->
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="btn-tab-general" tabindex="0">
               
               <!-- Section Header Logo -->
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                     <div>
                        <h2 class="h5 fw-bold mb-1">📷 1. Logo Header Utama</h2>
                        <p class="text-secondary small mb-0">Ganti berkas gambar logo pada header navigasi atas website.</p>
                     </div>
                     @if($headerLogo)
                        <span class="badge bg-success px-3 py-2 rounded-pill">Header Logo Kustom</span>
                     @else
                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Logo Standar Template</span>
                     @endif
                  </div>

                  <div class="row g-4">
                     <div class="col-md-5">
                        <div class="logo-preview-box h-100 d-flex flex-column align-items-center justify-content-center">
                           <p class="small text-muted mb-3 fw-medium">Pratinjau Logo Header:</p>
                           <div class="p-3 rounded-4 bg-body border w-100 mb-3 text-center shadow-sm">
                              <img src="{{ $headerLogo && file_exists(public_path($headerLogo)) ? asset($headerLogo) : asset('images/logo-indraco-est.png') }}" alt="Header Logo" class="img-fluid" style="max-height: 55px; object-fit: contain;">
                           </div>
                        </div>
                     </div>
                     <div class="col-md-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body">
                           <form action="{{ route('admin.setting.update-logo') }}" method="POST" enctype="multipart/form-data">
                              @csrf
                              <div class="mb-3">
                                 <label class="form-label small fw-semibold">Pilih Berkas Logo Header (PNG / SVG)</label>
                                 <input type="file" name="header_logo" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
                              </div>
                              <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-semibold">💾 Simpan Logo Header</button>
                           </form>
                        </div>
                     </div>
                  </div>
               </section>

               <!-- Section Footer Logo -->
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                     <div>
                        <h2 class="h5 fw-bold mb-1">👣 2. Logo Footer Website</h2>
                        <p class="text-secondary small mb-0">Ganti berkas gambar logo pada area footer bagian bawah.</p>
                     </div>
                  </div>
                  <div class="row g-4">
                     <div class="col-md-5">
                        <div class="logo-preview-box h-100 d-flex flex-column align-items-center justify-content-center">
                           <div class="p-3 rounded-4 border w-100 mb-3 text-center shadow-sm" style="background-color: #004b49;">
                              <img src="{{ $footerLogo && file_exists(public_path($footerLogo)) ? asset($footerLogo) : asset('images/logo-indraco-invert.png') }}" alt="Footer Logo" class="img-fluid" style="max-height: 55px; object-fit: contain;">
                           </div>
                        </div>
                     </div>
                     <div class="col-md-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body">
                           <form action="{{ route('admin.setting.update-footer-logo') }}" method="POST" enctype="multipart/form-data">
                              @csrf
                              <div class="mb-3">
                                 <label class="form-label small fw-semibold">Pilih Berkas Logo Footer</label>
                                 <input type="file" name="footer_logo" class="form-control rounded-3 bg-body-tertiary" accept="image/*" required>
                              </div>
                              <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-semibold">💾 Simpan Logo Footer</button>
                           </form>
                        </div>
                     </div>
                  </div>
               </section>

               <!-- Section Sosmed Icons -->
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🌐 3. Pengaturan Media Sosial</h2>
                  <form action="{{ route('admin.setting.update-sosmed') }}" method="POST">
                     @csrf
                     <div class="row g-3 mb-4">
                        @foreach($sosmed as $key => $data)
                           <div class="col-12 col-md-6">
                              <div class="sosmed-item-card h-100">
                                 <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <span class="fw-bold">{{ $data['name'] }}</span>
                                    <div class="form-check form-switch mb-0">
                                       <input class="form-check-input" type="checkbox" name="sosmed_{{ $key }}_active" id="active_{{ $key }}" {{ $data['active'] == '1' ? 'checked' : '' }}>
                                       <label class="form-check-label small" for="active_{{ $key }}">{{ $data['active'] == '1' ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                 </div>
                                 <input type="text" name="sosmed_{{ $key }}_url" class="form-control rounded-3 bg-body-tertiary" value="{{ $data['url'] }}">
                              </div>
                           </div>
                        @endforeach
                     </div>
                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Media Sosial</button>
                     </div>
                  </form>
               </section>

            </div>

            <!-- ==========================================
                 TAB 2: LANDING PAGE / HOME
            =========================================== -->
            <div class="tab-pane fade" id="tab-home" role="tabpanel" aria-labelledby="btn-tab-home" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🏠 Pengaturan Teks &amp; Gambar Landing Page / Home</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="home">
                     <input type="hidden" name="active_tab" value="tab-home">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <h3 class="h6 fw-bold text-custom-1 mb-3">📍 Section About Us (Halaman Utama)</h3>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Section About</label>
                           <input type="text" name="page_home_about_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['home']['about_title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Headline Subtitle</label>
                           <input type="text" name="page_home_about_headline" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['home']['about_headline'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Deskripsi Konten About</label>
                           <textarea name="page_home_about_content" class="form-control rounded-3 bg-body-tertiary" rows="3">{{ $pageSettings['home']['about_content'] }}</textarea>
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Ganti Gambar Section About Us</label>
                           <input type="file" name="page_home_about_image" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                           <img src="{{ asset($pageSettings['home']['about_image']) }}" alt="About Image" class="mt-2 rounded-3" style="max-height: 100px; object-fit: cover;">
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Konten Landing Page</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 3: ABOUT US PAGE
            =========================================== -->
            <div class="tab-pane fade" id="tab-about" role="tabpanel" aria-labelledby="btn-tab-about" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">ℹ️ Pengaturan Halaman About Us (/about)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="about">
                     <input type="hidden" name="active_tab" value="tab-about">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Banner Header</label>
                           <input type="text" name="page_about_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['about']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Subjudul Banner</label>
                           <input type="text" name="page_about_subtitle" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['about']['subtitle'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Headline Utama</label>
                           <input type="text" name="page_about_heading" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['about']['heading'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Deskripsi Halaman</label>
                           <textarea name="page_about_content" class="form-control rounded-3 bg-body-tertiary" rows="4">{{ $pageSettings['about']['content'] }}</textarea>
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Ganti Gambar Header About Us</label>
                           <input type="file" name="page_about_image" class="form-control rounded-3 bg-body-tertiary" accept="image/*">
                           <img src="{{ asset($pageSettings['about']['image']) }}" alt="About" class="mt-2 rounded-3" style="max-height: 100px; object-fit: contain;">
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Halaman About Us</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 4: BUSINESS PAGE
            =========================================== -->
            <div class="tab-pane fade" id="tab-business" role="tabpanel" aria-labelledby="btn-tab-business" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">💼 Pengaturan Halaman Business (/businesses)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="business">
                     <input type="hidden" name="active_tab" value="tab-business">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Banner</label>
                           <input type="text" name="page_business_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['business']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Subjudul Banner</label>
                           <input type="text" name="page_business_subtitle" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['business']['subtitle'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Headline Utama</label>
                           <input type="text" name="page_business_heading" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['business']['heading'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Deskripsi Halaman</label>
                           <textarea name="page_business_content" class="form-control rounded-3 bg-body-tertiary" rows="4">{{ $pageSettings['business']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Halaman Business</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 5: ONLINE STORE PAGE
            =========================================== -->
            <div class="tab-pane fade" id="tab-store" role="tabpanel" aria-labelledby="btn-tab-store" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🛒 Pengaturan Halaman Online Store (/store)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="store">
                     <input type="hidden" name="active_tab" value="tab-store">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Banner Store</label>
                           <input type="text" name="page_store_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['store']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Subjudul Banner</label>
                           <input type="text" name="page_store_subtitle" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['store']['subtitle'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Headline Utama</label>
                           <input type="text" name="page_store_heading" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['store']['heading'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Deskripsi Halaman</label>
                           <textarea name="page_store_content" class="form-control rounded-3 bg-body-tertiary" rows="3">{{ $pageSettings['store']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Halaman Store</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 6: CAREERS PAGE
            =========================================== -->
            <div class="tab-pane fade" id="tab-careers" role="tabpanel" aria-labelledby="btn-tab-careers" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🎯 Pengaturan Halaman Careers (/careers)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="careers">
                     <input type="hidden" name="active_tab" value="tab-careers">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Banner Careers</label>
                           <input type="text" name="page_careers_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['careers']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Subjudul Banner</label>
                           <input type="text" name="page_careers_subtitle" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['careers']['subtitle'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Headline Utama</label>
                           <input type="text" name="page_careers_heading" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['careers']['heading'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Deskripsi Halaman</label>
                           <textarea name="page_careers_content" class="form-control rounded-3 bg-body-tertiary" rows="3">{{ $pageSettings['careers']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Halaman Careers</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 7: CONTACT US PAGE
            =========================================== -->
            <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="btn-tab-contact" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">📞 Pengaturan Halaman Contact Us (/contact)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="page_group" value="contact">
                     <input type="hidden" name="active_tab" value="tab-contact">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Banner Contact</label>
                           <input type="text" name="page_contact_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['contact']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Subjudul Banner</label>
                           <input type="text" name="page_contact_subtitle" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['contact']['subtitle'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Alamat Kantor Resmi</label>
                           <input type="text" name="page_contact_address" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['contact']['address'] }}">
                        </div>
                        <div class="row g-3 mb-3">
                           <div class="col-md-6">
                              <label class="form-label small fw-semibold">Nomor Telepon Kontak</label>
                              <input type="text" name="page_contact_phone" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['contact']['phone'] }}">
                           </div>
                           <div class="col-md-6">
                              <label class="form-label small fw-semibold">Alamat Email Resmi</label>
                              <input type="text" name="page_contact_email" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['contact']['email'] }}">
                           </div>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Halaman Contact</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 8: PRIVACY POLICY
            =========================================== -->
            <div class="tab-pane fade" id="tab-privacy" role="tabpanel" aria-labelledby="btn-tab-privacy" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🔒 Pengaturan Privacy Policy (/privacy-policy)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST">
                     @csrf
                     <input type="hidden" name="page_group" value="privacy">
                     <input type="hidden" name="active_tab" value="tab-privacy">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Halaman</label>
                           <input type="text" name="page_privacy_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['privacy']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Isi Teks Dokumen Privacy Policy</label>
                           <textarea name="page_privacy_content" class="form-control rounded-3 bg-body-tertiary" rows="10">{{ $pageSettings['privacy']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Privacy Policy</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 9: TERMS & CONDITIONS
            =========================================== -->
            <div class="tab-pane fade" id="tab-terms" role="tabpanel" aria-labelledby="btn-tab-terms" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">📜 Pengaturan Terms &amp; Conditions (/terms-and-conditions)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST">
                     @csrf
                     <input type="hidden" name="page_group" value="terms">
                     <input type="hidden" name="active_tab" value="tab-terms">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Halaman</label>
                           <input type="text" name="page_terms_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['terms']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Isi Teks Dokumen Terms &amp; Conditions</label>
                           <textarea name="page_terms_content" class="form-control rounded-3 bg-body-tertiary" rows="10">{{ $pageSettings['terms']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Terms &amp; Conditions</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 10: INFORMATION ON DATA PROTECTION
            =========================================== -->
            <div class="tab-pane fade" id="tab-dataprotection" role="tabpanel" aria-labelledby="btn-tab-dataprotection" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">🛡️ Information On Data Protection (/data-protection)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST">
                     @csrf
                     <input type="hidden" name="page_group" value="dataprotection">
                     <input type="hidden" name="active_tab" value="tab-dataprotection">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Halaman</label>
                           <input type="text" name="page_dataprotection_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['dataprotection']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Isi Teks Dokumen Perlindungan Data</label>
                           <textarea name="page_dataprotection_content" class="form-control rounded-3 bg-body-tertiary" rows="10">{{ $pageSettings['dataprotection']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Data Protection</button>
                     </div>
                  </form>
               </section>
            </div>

            <!-- ==========================================
                 TAB 11: HELP / FAQ
            =========================================== -->
            <div class="tab-pane fade" id="tab-help" role="tabpanel" aria-labelledby="btn-tab-help" tabindex="0">
               <section class="bg-body-secondary rounded-4 p-4 shadow-sm mb-4">
                  <h2 class="h5 fw-bold mb-3 border-bottom pb-2">❓ Help Center &amp; FAQ (/help)</h2>
                  <form action="{{ route('admin.setting.update-page-content') }}" method="POST">
                     @csrf
                     <input type="hidden" name="page_group" value="help">
                     <input type="hidden" name="active_tab" value="tab-help">

                     <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body">
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Judul Halaman</label>
                           <input type="text" name="page_help_title" class="form-control rounded-3 bg-body-tertiary" value="{{ $pageSettings['help']['title'] }}">
                        </div>
                        <div class="mb-3">
                           <label class="form-label small fw-semibold">Isi Teks Pertanyaan Umum (FAQ) &amp; Pusat Bantuan</label>
                           <textarea name="page_help_content" class="form-control rounded-3 bg-body-tertiary" rows="10">{{ $pageSettings['help']['content'] }}</textarea>
                        </div>
                     </div>

                     <div class="text-end">
                        <button type="submit" class="btn btn-custom-1 rounded-pill px-4 fw-bold">💾 Simpan Help &amp; FAQ</button>
                     </div>
                  </form>
               </section>
            </div>

         </div>

      </div>

   </div>
</main>
@endsection
