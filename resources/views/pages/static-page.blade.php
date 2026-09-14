@extends('layouts.app')

@section('title', 'INDRACO – ' . $title)

@section('content')
<main id="content" tabindex="-1">
   <!-- Section Banner -->
   <section aria-label="section banner" class="section-banner mb-5">
      <div class="container-sm banner">
         <div class="row banner-wrapper align-items-center">
            <div class="col col-12 z-0">
               <h2 class="banner-title text-uppercase">{{ $title }}</h2>
            </div>
            <div class="col col-12 z-2">
               <h3 class="banner-text">INDRACO Official Policy &amp; Information</h3>
            </div>
            <div class="col col-12 z-3">
               <div class="banner-sosmed d-flex justify-content-center justify-content-lg-end">
                  <x-sosmed />
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Content Section -->
   <section aria-label="static page content" class="container mb-5">
      <div class="bg-body-secondary p-4 p-md-5 rounded-4 shadow-sm">
         <h1 class="h3 fw-bold mb-4 text-custom-1">{{ $title }}</h1>
         <div class="lh-lg text-body" style="font-size: 1.05rem;">
            {!! nl2br(e($content)) !!}
         </div>
      </div>
   </section>
</main>
@endsection
