<!DOCTYPE html>
<html lang="zxx" dir="{{ $currentLanguageInfo->direction == 1 ? 'rtl' : 'ltr' }}">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="ie=edge" />
  <meta name="description" content="@yield('meta-description')">
  <meta name="keywords" content="@yield('meta-keywords')">

  <meta property="og:title" content="@yield('og-title')" />
  <meta property="og:description" content="@yield('og-description')" />
  <meta property="og:image" content="@yield('og-image')" />


  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Title -->
  <title>@yield('pageHeading') {{ '| ' . $websiteInfo->website_title }}</title>
  <!-- Favicon Icon -->
  <link rel="shortcut icon" href="{{ asset('assets/admin/img/' . $websiteInfo->favicon) }}" type="image/x-icon">
  {{-- include styles --}}
  @includeIf('frontend.partials.styles')
  @yield('custom-style')
</head>

<body class="@yield('body-class')">
  <div class="page-wrapper">

    <!-- Preloader -->
    <div class="preloader" style="background-image:url({{ asset('assets/admin/img/' . $websiteInfo->preloader) }})">
    </div>
    <script>
      // Never allow the public homepage preloader to block the UI indefinitely.
      window.setTimeout(function () {
        var preloader = document.querySelector('.preloader');
        if (preloader) {
          preloader.style.opacity = '0';
          preloader.style.pointerEvents = 'none';
          window.setTimeout(function () { preloader.style.display = 'none'; }, 250);
        }
      }, 2500);
    </script>
    <div class="request-loader">
      <img src="{{ asset('assets/admin/img/loader.gif') }}" alt="loader">
    </div>



    <!-- Header Part Start -->
    @includeIf('frontend.partials.header.header-nav')
    <!-- Header Part End -->

    @yield('hero-section')

    @yield('content')

    @includeIf('frontend.partials.popups')


    @includeIf('frontend.partials.footer.footer')

  </div>
  <!--End pagewrapper-->

  {{-- modals --}}
  @yield('modals')
  {{-- include scripts --}}
  <script>
    "use strict";
    var rtl = {{ $currentLanguageInfo->direction }};
  </script>
  @includeIf('frontend.partials.scripts')

  {{-- additional script --}}
  @yield('script')
  @yield('custom-script')

  {{-- Cookie alert dialog start --}}
  @if (!empty($cookieAlertInfo) && $cookieAlertInfo->cookie_alert_status == 1)
    <div class="cookie">
      @include('cookie-consent::index')
    </div>
  @endif
  {{-- Cookie alert dialog end --}}

</body>

</html>
