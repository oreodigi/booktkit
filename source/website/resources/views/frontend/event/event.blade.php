@extends('frontend.layout')
@section('body-class', 'booktkit-events-page')
@section('pageHeading')
    @if (!empty($pageHeading))
        {{ $pageHeading->event_page_title ?? __('Events') }}
    @else
        {{ __('Events') }}
    @endif
@endsection

@php
    $metaKeywords = !empty($seo->meta_keyword_event) ? $seo->meta_keyword_event : '';
    $metaDescription = !empty($seo->meta_description_event) ? $seo->meta_description_event : '';
@endphp
@section('meta-keywords', "{{ $metaKeywords }}")
@section('meta-description', "$metaDescription")

@section('hero-section')
    <!-- Page Banner Start -->
    <section class="page-banner overlay pt-120 pb-125 rpt-90 rpb-95 lazy"
        data-bg="{{ asset('assets/admin/img/' . $basicInfo->breadcrumb) }}">
        <div class="container">
            <div class="banner-inner">
                <h2 class="page-title">
                    @if (!empty($pageHeading))
                        {{ $pageHeading->event_page_title ?? __('Events') }}
                    @else
                        {{ __('Events') }}
                    @endif
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index') }}">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active">
                            @if (!empty($pageHeading))
                                {{ $pageHeading->event_page_title ?? __('Events') }}
                            @else
                                {{ __('Events') }}
                            @endif
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>
    <!-- Page Banner End -->
@endsection
@section('content')
    <!-- Event Page Start -->
    <section class="event-page-section py-120 rpy-100">
        <div class="container container-custom">
            <div class="row">
                <div class="col-lg-3">
                    @includeIf('frontend.event.event-sidebar')
                </div>
                <div class="col-lg-9">
                    <div class="event-page-content">
                        <div class="row">
                            @if (count($information['events']) > 0)
                                @foreach ($information['events'] as $event)
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="event-item">
                                            <div class="event-image">
                                                <a href="{{ route('event.details', [$event->slug, $event->id]) }}">
                                                    <img class="lazy"
                                                        data-src="{{ asset('assets/admin/img/event/thumbnail/' . $event->thumbnail) }}"
                                                        alt="Event">
                                                </a>
                                            </div>
                                            <div class="event-content">
                                                <ul class="time-info" dir="ltr">
                                                    @php
                                                        if ($event->date_type == 'multiple') {
                                                            $event_date = eventLatestDates($event->id);
                                                            $date = strtotime(@$event_date->start_date);
                                                        } else {
                                                            $date = strtotime($event->start_date);
                                                        }
                                                    @endphp
                                                    <li>
                                                        <i class="far fa-calendar-alt"></i>
                                                        <span>
                                                            {{ \Carbon\Carbon::parse($date)->timezone($websiteInfo->timezone)->translatedFormat('d M') }}
                                                        </span>
                                                    </li>

                                                    <li>
                                                        <i class="far fa-hourglass"></i>
                                                        <span title="Event Duration">
                                                            {{ $event->date_type == 'multiple' ? @$event_date->duration : $event->duration }}
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <i class="far fa-clock"></i>
                                                        <span>
                                                            @php
                                                                $start_time = strtotime($event->start_time);
                                                            @endphp
                                                            {{ \Carbon\Carbon::parse($start_time)->timezone($websiteInfo->timezone)->translatedFormat('h:s A') }}
                                                        </span>
                                                    </li>
                                                </ul>
                                                @if ($event->organizer_id != null)
                                                    @php
                                                        $organizer = App\Models\Organizer::where(
                                                            'id',
                                                            $event->organizer_id,
                                                        )->first();
                                                    @endphp
                                                    @if ($organizer)
                                                        <a href="{{ route('frontend.organizer.details', [$organizer->id, str_replace(' ', '-', $organizer->username)]) }}"
                                                            class="organizer">{{ __('By') }}&nbsp;&nbsp;{{ @$organizer->organizer_info->name }}</a>
                                                    @endif
                                                @else
                                                    @php
                                                        $admin = App\Models\Admin::first();
                                                    @endphp
                                                    <a href="{{ route('frontend.organizer.details', [$admin->id, str_replace(' ', '-', $admin->username), 'admin' => 'true']) }}"
                                                        class="organizer">{{ __('By') }} {{ $admin->username }}</a>
                                                @endif
                                                <h5>
                                                    <a href="{{ route('event.details', [$event->slug, $event->id]) }}">
                                                        @if (strlen($event->title) > 70)
                                                            {{ mb_substr($event->title, 0, 70) . '...' }}
                                                        @else
                                                            {{ $event->title }}
                                                        @endif
                                                    </a>
                                                </h5>
                                                @php
                                                    $desc = strip_tags($event->description);
                                                @endphp

                                                @if (strlen($desc) > 100)
                                                    <p class="event-description">{{ mb_substr($desc, 0, 100) . '....' }}
                                                    </p>
                                                @else
                                                    <p class="event-description">{{ $desc }}</p>
                                                @endif
                                                @php
                                                    $startingPrice = app(\App\Services\Events\EventStartingPriceService::class)
                                                        ->resolve($event->id);
                                                @endphp
                                                @if ($basicInfo->google_map_status == 1 && request()->input('location'))
                                                    <span class="font-sm icon-start d-block">
                                                        <i class="fas fa-map-signs"></i>
                                                        {{ number_format($event->distance / 1000, 2) }}
                                                        {{ __('km') }}
                                                    </span>
                                                @endif
                                                <div class="price-remain">
                                                    <div class="location">
                                                        @if ($event->event_type == 'venue')
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span>
                                                                {{ $event->address }}
                                                            </span>
                                                        @else
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span>{{ __('Online') }}</span>
                                                        @endif
                                                    </div>
                                                    <span>

                                                        @if ($startingPrice['available'])
                                                            <span class="price" dir="ltr">
                                                                @if ($startingPrice['free'])
                                                                    {{ __('Free') }}
                                                                @else
                                                                    {{ __('From') }} {{ symbolPrice($startingPrice['price']) }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            @if (Auth::guard('customer')->check())
                                                @php
                                                    $customer_id = Auth::guard('customer')->user()->id;
                                                    $event_id = $event->id;
                                                    $checkWishList = checkWishList($event_id, $customer_id);
                                                @endphp
                                            @else
                                                @php
                                                    $checkWishList = false;
                                                @endphp
                                            @endif
                                            <a href="{{ $checkWishList == false ? route('addto.wishlist', $event->id) : route('remove.wishlist', $event->id) }}"
                                                class="wishlist-btn {{ $checkWishList == true ? 'bg-success' : '' }}">
                                                <i class="far fa-bookmark"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-lg-12">
                                    <h3 class="text-center">{{ __('No Event Found') }}</h3>
                                </div>
                            @endif
                        </div>
                        <ul class="pagination flex-wrap pt-10">
                            {{ $information['events']->links() }}
                        </ul>
                        @if (!empty(showAd(3)))
                            <div class="text-center mt-4">
                                {!! showAd(3) !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Event Page End -->

    <form id="filtersForm" class="d-none" action="{{ route('events') }}" method="GET">
        <input type="hidden" id="category-id" name="category"
            value="{{ !empty(request()->input('category')) ? request()->input('category') : '' }}">

        <input type="hidden" id="event" name="event"
            value="{{ !empty(request()->input('event')) ? request()->input('event') : '' }}">

        <input type="hidden" id="min-id" name="min"
            value="{{ !empty(request()->input('min')) ? request()->input('min') : '' }}">

        <input type="hidden" id="max-id" name="max"
            value="{{ !empty(request()->input('max')) ? request()->input('max') : '' }}">

        <input type="hidden" name="search-input"
            value="{{ !empty(request()->input('search-input')) ? request()->input('search-input') : '' }}">
        <input type="hidden" name="location"
            value="{{ !empty(request()->input('location')) ? request()->input('location') : '' }}">

        <input type="hidden" id="dates-id" name="dates"
            value="{{ !empty(request()->input('dates')) ? request()->input('dates') : '' }}">

        <button type="submit" id="submitBtn"></button>
    </form>
@endsection

@section('custom-script')
    <script type="text/javascript" src="{{ asset('assets/front/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/daterangepicker.min.js') }}"></script>

    <script>
        let min_price = Number({!! json_encode((float) $information['min']) !!});
        let max_price = Number({!! json_encode((float) $information['max']) !!});
        let symbol = {!! json_encode($information['currency_symbol']) !!};
        let position = {!! json_encode($information['currency_symbol_position']) !!};
        let curr_min = Number({!! json_encode((float) $information['current_min']) !!});
        let curr_max = Number({!! json_encode((float) $information['current_max']) !!});

        curr_min = Math.max(min_price, Math.min(curr_min, max_price));
        curr_max = Math.max(min_price, Math.min(curr_max, max_price));

        if (curr_min > curr_max) {
            curr_min = min_price;
            curr_max = max_price;
        }

        const countryUrl = "{{ route('frontend.get_country') }}";
        const stateUrl = "{{ route('frontend.get_state') }}";
        const cityUrl = "{{ route('frontend.get_city') }}";
    </script>

    <script src="{{ asset('assets/front/js/custom_script.js') }}"></script>
    @if ($basicInfo->google_map_status == 1)
        <script src="{{ asset('assets/front/js/geo-search.js') }}"></script>
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ $basicInfo->google_map_api_key }}&libraries=places&callback=initMap"></script>
    @endif
@endsection
