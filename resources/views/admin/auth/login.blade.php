@extends('layouts.app')

@section('title', 'INDRACO – Masuk Akun Admin')

@push('styles')
   <style>
      .auth-card {
         max-width: 520px;
         margin: 0 auto;
      }
      .btn-social {
         border: 1px solid rgba(128, 128, 128, 0.2);
         transition: all 0.2s ease;
      }
      .btn-social:hover {
         background-color: rgba(0, 0, 0, 0.04);
      }
      [data-bs-theme="dark"] .btn-social:hover {
         background-color: rgba(255, 255, 255, 0.08);
      }
      .password-toggle {
         cursor: pointer;
      }
   </style>
@endpush

@section('content')
   <!-- Section Form Login -->
   <section aria-label="section login form" class="container my-5 py-md-4">
      <div class="auth-card bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm">
         <div class="text-center mb-4">
            <h1 class="h3 fw-bold mb-2">Masuk ke Akun Anda</h1>
            <p class="text-secondary small">Akses Panel Admin INDRACO Coffee & Manajemen Data.</p>
         </div>

         @if($errors->any())
            <div class="alert alert-danger rounded-4 py-2 px-3 small mb-4">
               {{ $errors->first() }}
            </div>
         @endif

         <form action="{{ route('admin.login.post') }}" method="POST" aria-label="Form Login">
            @csrf

            <!-- Email / Username -->
            <div class="form-group mb-3">
               <label for="email" class="form-label fw-medium small">Alamat Email / Username *</label>
               <input type="email" name="email" id="email" class="form-control rounded-pill bg-body-tertiary px-3 @error('email') is-invalid @enderror" placeholder="contoh: admin@indraco.com" value="{{ old('email') }}" required autocomplete="email">
               @error('email')
                  <div class="invalid-feedback ps-3">{{ $message }}</div>
               @enderror
            </div>

            <!-- Password -->
            <div class="form-group mb-3">
               <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="password" class="form-label fw-medium small mb-0">Kata Sandi *</label>
                  <a href="#" class="small text-decoration-none fw-semibold">Lupa Kata Sandi?</a>
               </div>
               <div class="input-group">
                  <input type="password" name="password" id="password" class="form-control rounded-start-pill bg-body-tertiary px-3 @error('password') is-invalid @enderror" placeholder="Masukkan kata sandi Anda" required autocomplete="current-password">
                  <button type="button" class="btn bg-body-tertiary border rounded-end-pill password-toggle px-3" aria-label="Tampilkan kata sandi" onclick="togglePasswordVisibility('password', this)">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                     </svg>
                  </button>
               </div>
               @error('password')
                  <div class="text-danger small mt-1 ps-3">{{ $message }}</div>
               @enderror
            </div>

            <!-- Remember Me -->
            <div class="form-check mb-4">
               <input class="form-check-input" type="checkbox" name="remember" id="remember-me">
               <label class="form-check-label small" for="remember-me">
                  Ingat saya di perangkat ini
               </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-lg btn-custom-1 rounded-pill w-100 mb-3 fw-semibold">MASUK SEKARANG</button>

            <!-- Divider -->
            <div class="d-flex align-items-center my-4">
               <hr class="flex-grow-1 m-0 opacity-25">
               <span class="px-3 small opacity-50 fw-medium">atau masuk dengan</span>
               <hr class="flex-grow-1 m-0 opacity-25">
            </div>

            <!-- Social Login -->
            <div class="row g-2 mb-4">
               <div class="col-6">
                  <button type="button" class="btn btn-social rounded-pill w-100 d-flex align-items-center justify-content-center gap-2 py-2 small fw-medium">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18" height="18">
                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                        <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571c.001-.001.002-.001.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                     </svg>
                     Google
                  </button>
               </div>
               <div class="col-6">
                  <button type="button" class="btn btn-social rounded-pill w-100 d-flex align-items-center justify-content-center gap-2 py-2 small fw-medium">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="#0A66C2">
                        <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                     </svg>
                     LinkedIn
                  </button>
               </div>
            </div>

            <!-- Footer Link -->
            <div class="text-center">
               <p class="small mb-0">Kembali ke <a href="{{ route('home') }}" class="fw-bold text-decoration-none">Halaman Utama Website</a></p>
            </div>
         </form>
      </div>
   </section>
@endsection

@push('scripts')
   <script>
      function togglePasswordVisibility(inputId, btn) {
         const input = document.getElementById(inputId);
         if (!input) return;
         const isPassword = input.getAttribute('type') === 'password';
         input.setAttribute('type', isPassword ? 'text' : 'password');
         btn.innerHTML = isPassword 
            ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
      }
   </script>
@endpush
