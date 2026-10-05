@php
  $slides = isset($heroSlides) ? $heroSlides : collect();
@endphp
@if($slides->count())
<section class="bkt-hero-slider {{ !empty($mobileHero) ? 'bkt-hero-mobile' : '' }}" data-bkt-hero>
  <div class="bkt-hero-track">
    @foreach($slides as $slide)
      @php
        $eventContent = $slide->event?->content ?? null;
        $eventSlug = $eventContent?->slug;
        $targetUrl = $slide->event_id && $eventSlug
          ? route('event.details', [$eventSlug, $slide->event_id])
          : ($slide->custom_url ?: route('events'));
      @endphp
      <article class="bkt-hero-slide {{ $loop->first ? 'is-active' : '' }}" data-slide="{{ $loop->index }}">
        @if($slide->media_type === 'video' && $slide->video)
          <video class="bkt-hero-media" autoplay muted loop playsinline preload="{{ $loop->first ? 'auto' : 'metadata' }}" @if($slide->image) poster="{{ asset('assets/admin/img/hero-slides/'.$slide->image) }}" @endif>
            <source src="{{ asset('assets/admin/img/hero-slides/'.$slide->video) }}">
          </video>
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
