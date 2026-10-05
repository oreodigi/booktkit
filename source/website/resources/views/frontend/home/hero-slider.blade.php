@php
  $slides = isset($heroSlides) ? $heroSlides : collect();
@endphp
@if($slides->count())
<section class="bkt-hero-slider {{ !empty($mobileHero) ? 'bkt-hero-mobile' : '' }}" data-bkt-hero>
  <div class="bkt-hero-track">
    @foreach($slides as $slide)
      @php
        $eventContent = $slide->event?->information ?? null;
        $eventSlug = $eventContent?->slug;
        $targetUrl = $slide->event_id && $eventSlug
          ? route('event.details', [$eventSlug, $slide->event_id])
          : ($slide->custom_url ?: route('events'));
      @endphp
      <article class="bkt-hero-slide {{ $loop->first ? 'is-active' : '' }}" data-slide="{{ $loop->index }}">
        @if($slide->media_type === 'video')
          @if(($slide->video_source ?: 'upload') === 'youtube' && $slide->video_url)
            @php
              $parts = parse_url($slide->video_url); $youtubeId = null;
              if ($parts && !empty($parts['host'])) {
                $host = strtolower(preg_replace('/^www\\./', '', $parts['host']));
                if ($host === 'youtu.be') $youtubeId = trim($parts['path'] ?? '', '/');
                elseif (in_array($host, ['youtube.com','m.youtube.com','music.youtube.com'], true)) {
                  if (preg_match('~^/(?:embed|shorts|live)/([^/?]+)~', $parts['path'] ?? '', $m)) $youtubeId = $m[1];
                  else { parse_str($parts['query'] ?? '', $q); $youtubeId = $q['v'] ?? null; }
                }
              }
            @endphp
            @if($youtubeId)
              <div class="bkt-hero-media bkt-hero-youtube"><iframe src="https://www.youtube-nocookie.com/embed/{{ urlencode($youtubeId) }}?autoplay=1&mute=1&loop=1&playlist={{ urlencode($youtubeId) }}&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playsinline=1&rel=0" title="{{ $slide->title ?: __('BookTKIT video banner') }}" allow="autoplay; encrypted-media" referrerpolicy="strict-origin-when-cross-origin" tabindex="-1"></iframe></div>
            @endif
          @elseif($slide->video_source === 'vimeo' && $slide->video_url)
            @php
              $vimeoParts = parse_url($slide->video_url); $vimeoId = null;
              if ($vimeoParts && !empty($vimeoParts['host'])) {
                $vimeoHost = strtolower(preg_replace('/^www\\./', '', $vimeoParts['host']));
                if (in_array($vimeoHost, ['vimeo.com','player.vimeo.com'], true) && preg_match('~/(?:video/)?([0-9]+)(?:$|[/?#])~', $vimeoParts['path'] ?? '', $vm)) $vimeoId = $vm[1];
              }
            @endphp
            @if($vimeoId)
              <div class="bkt-hero-media bkt-hero-youtube"><iframe src="https://player.vimeo.com/video/{{ urlencode($vimeoId) }}?background=1&autoplay=1&muted=1&loop=1&controls=0&title=0&byline=0&portrait=0&dnt=1" title="{{ $slide->title ?: __('BookTKIT video banner') }}" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" tabindex="-1"></iframe></div>
            @endif
          @elseif($slide->video_source === 'url' && $slide->video_url)
            <video class="bkt-hero-media" autoplay muted loop playsinline preload="{{ $loop->first ? 'auto' : 'metadata' }}" @if($slide->image) poster="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" @endif><source src="{{ $slide->video_url }}"></video>
          @elseif($slide->video)
            <video class="bkt-hero-media" autoplay muted loop playsinline preload="{{ $loop->first ? 'auto' : 'metadata' }}" @if($slide->image) poster="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" @endif><source src="{{ asset('assets/admin/img/hero-slides/'.$slide->video) }}"></video>
          @elseif($slide->image)
            <img class="bkt-hero-media" src="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" alt="{{ $slide->title ?: __('BookTKIT event banner') }}">
          @endif
        @else
          <img class="bkt-hero-media" src="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" alt="{{ $slide->title ?: __('BookTKIT event banner') }}" @if(!$loop->first) loading="lazy" @endif>
        @endif
        <div class="bkt-hero-shade"></div>
        <div class="bkt-hero-inner">
          @if($slide->title)<h1>{{ $slide->title }}</h1>@endif
          @if($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
          @if($slide->button_text)
            <a class="bkt-hero-cta" href="{{ $targetUrl }}" @if($slide->open_new_tab) target="_blank" rel="noopener" @endif>{{ $slide->button_text }}</a>
          @endif
        </div>
      </article>
    @endforeach
  </div>
  @if($slides->count() > 1)
    <button class="bkt-hero-arrow bkt-prev" type="button" aria-label="{{ __('Previous banner') }}">&#10094;</button>
    <button class="bkt-hero-arrow bkt-next" type="button" aria-label="{{ __('Next banner') }}">&#10095;</button>
    <div class="bkt-hero-dots">@foreach($slides as $slide)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-dot="{{ $loop->index }}" aria-label="{{ __('Banner') }} {{ $loop->iteration }}"></button>@endforeach</div>
  @endif
</section>
@endif
