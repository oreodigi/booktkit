<header class="main-header">

  <!--Header-Upper-->
  <div class="header-upper py-25">
    <div class="container clearfix">

      <div class="header-inner">
        <div class="logo-outer">
          <div class="logo"><a href="{{ route('index') }}"><img
                src="{{ asset('assets/admin/img/' . $websiteInfo->logo) }}" alt="Logo"></a></div>
        </div>

        <div class="nav-outer clearfix ml-lg-auto">
          <!-- Main Menu -->
          <nav class="main-menu navbar-expand-xl">
            <div class="navbar-header">
              <div class="logo-mobile"><a href="{{ route('index') }}"><img
                    src="{{ asset('assets/admin/img/' . $websiteInfo->logo) }}" alt="Logo"></a></div>
              <div class="mobile-header-actions d-xl-none">
                <a class="mobile-header-icon" href="{{ route('contact') }}" aria-label="Support"><i class="fa fa-headphones"></i></a>
                @if (Auth::guard('customer')->check())
                  <a class="mobile-header-icon" href="{{ route('customer.dashboard') }}" aria-label="Account"><i class="fa fa-user-circle"></i></a>
                @elseif (Auth::guard('organizer')->check())
                  <a class="mobile-header-icon" href="{{ route('organizer.dashboard') }}" aria-label="Account"><i class="fa fa-user-circle"></i></a>
                @else
                  <a class="mobile-header-icon" href="{{ route('customer.login') }}" aria-label="Sign in"><i class="fa fa-user-circle"></i></a>
                @endif
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"
                  aria-controls="main-menu" aria-label="Open menu">
                  <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>
              </div>
            </div>

            <div class="navbar-collapse collapse clearfix" id="main-menu">
              <div class="mobile-menu-head d-xl-none"><span class="mobile-menu-title">{{ __('Menu') }}</span><button type="button" class="mobile-menu-close" aria-label="Close menu">&times;</button></div>
              @php
                $links = json_decode($menuInfos, true);
              @endphp
              <ul class="navigation clearfix">
                @foreach ($links as $link)
                  @php
                    $href = get_href($link, $currentLanguageInfo->id);
                  @endphp
                  @if (!array_key_exists('children', $link))
                    <li><a href="{{ $href }}" target="{{ $link['target'] }}">{{ __($link['text']) }}</a></li>
                  @else
                    <li class="dropdown">
                      <a href="{{ $href }}" target="{{ $link['target'] }}">
                        {{ $link['text'] }}
                        <i class="fa fa-angle-down"></i>
                      </a>
                      <ul>
                        @foreach ($link['children'] as $level2)
                          @php
                            $l2Href = get_href($level2, $currentLanguageInfo->id);
                          @endphp
                          <li>
                            <a href="{{ $l2Href }}" target="{{ $level2['target'] }}">{{ __($level2['text']) }}</a>
                          </li>
                        @endforeach
                      </ul>
                    </li>
                  @endif
                @endforeach
              </ul>

              <div class="menu-right">
                <form class="language-switcher" action="{{ route('change_language') }}" method="get">
                  <select name="lang_code" id="language" class="form-control" onchange="this.form.submit()">
                    @foreach ($allLanguageInfos as $item)
                      <option value="{{ $item->code }}"
                        {{ $item->code == $currentLanguageInfo->code ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                  </select>
                </form>
                @if (isset($allCurrencyInfos) && $allCurrencyInfos->count() > 0)
                  <form action="{{ route('change_currency') }}" method="get" class="ml-2 mr-1 desktop-currency-selector">
                    <select name="currency_id" class="form-control" onchange="this.form.submit()">
                      @foreach ($allCurrencyInfos as $currency)
                        <option value="{{ $currency->id }}"
                          {{ !empty($currentCurrencyInfo) && $currency->id == $currentCurrencyInfo->id ? 'selected' : '' }}>
                          {{ $currency->text }} ({{ $currency->symbol }})
                        </option>
                      @endforeach
                    </select>
                  </form>
                @endif
                @if (!Auth::guard('customer')->check())
                  <div class="dropdown auth-menu-entry customer-menu-entry mobile-auth-visible">
                    <a class="menu-btn mr-1" href="{{ route('customer.login') }}">{{ __('Customer Login') }}</a>
                  </div>
                @else
                  <div class="dropdown">
                    <button type="button" class="menu-btn dropdown-toggle mr-1"
                      data-toggle="dropdown">{{ trim((Auth::guard('customer')->user()->fname ?? '') . ' ' . (Auth::guard('customer')->user()->lname ?? '')) ?: Auth::guard('customer')->user()->email }}</button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item" href="{{ route('customer.dashboard') }}">{{ __('Dashboard') }}</a>
                      <a class="dropdown-item" href="{{ route('customer.logout') }}">{{ __('Logout') }}</a>
                    </div>
                  </div>
                @endif
                @if (!Auth::guard('organizer')->check())
                  <div class="dropdown auth-menu-entry organizer-menu-entry guest-organizer-entry">
                    <a class="menu-btn" href="{{ route('organizer.login') }}">{{ __('Organizer Login') }}</a>
                  </div>
                @else
                  <div class="dropdown">
                    <button type="button" class="menu-btn dropdown-toggle mr-1"
                      data-toggle="dropdown">{{ Auth::guard('organizer')->user()->username }}</button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item" href="{{ route('organizer.dashboard') }}">{{ __('Dashboard') }}</a>
                      <a class="dropdown-item" href="{{ route('organizer.logout') }}">{{ __('Logout') }}</a>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </nav>
          <!-- Main Menu End-->
        </div>
      </div>
    </div>
  </div>
  <!--End Header Upper-->
</header>
<div class="mobile-menu-backdrop"></div>
<script>
document.addEventListener("DOMContentLoaded",function(){var t=document.querySelector(".navbar-toggle"),c=document.querySelector(".mobile-menu-close"),b=document.querySelector(".mobile-menu-backdrop"),m=document.getElementById("main-menu");function closeMenu(){if(m){m.classList.remove("show");document.body.classList.remove("mobile-menu-open")}}function openMenu(){document.body.classList.add("mobile-menu-open")}if(t)t.addEventListener("click",function(){setTimeout(function(){m&&m.classList.contains("show")?openMenu():closeMenu()},10)});if(c)c.addEventListener("click",closeMenu);if(b)b.addEventListener("click",closeMenu);document.querySelectorAll("#main-menu a").forEach(function(a){a.addEventListener("click",function(){if(!a.closest("li.dropdown")||a.closest(".dropdown-menu"))closeMenu()})})});
</script>
