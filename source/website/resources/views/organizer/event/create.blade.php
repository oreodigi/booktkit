@extends('organizer.layout')

@section('content')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/event-create-wizard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/event-media-controls.css') }}">
    <div class="page-header">
        <h4 class="page-title">{{ __('Add Event') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('organizer.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Event Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a
                    href="{{ route('choose-event-type', ['language' => $defaultLang->code]) }}">{{ __('Choose Event Type') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Add Event') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add Event') }}</div>
                    <a class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('organizer.event_management.event', ['language' => $defaultLang->code]) }}">
                        <span class="btn-label">
                            <i class="fas fa-backward"></i>
                        </span>
                        {{ __('Back') }}
                    </a>
                    @if ((int) ($settings->ai_system_status ?? 1) === 1)
                        <button type="button" class="btn btn-primary btn-sm float-right listing-ai-field-btn mr-2"
                            id="aiGenerateItemSeoBtn" data-field="" data-lang=""
                            data-title="{{ __('AI Generate Event Content') }}">
                            <i class="fas fa-magic"></i> {{ __('Generate All Content') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="alert alert-danger pb-1 dis-none" id="eventErrors">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>
                            <div class="col-lg-12">
                                <label for="" class="mb-2"><strong>{{ __('Gallery Images') }} **</strong></label>
                                <form action="{{ route('organizer.event.imagesstore') }}" id="my-dropzone"
                                    enctype="multipart/formdata" class="dropzone create">
                                    @csrf
                                    <div class="fallback">
                                        <input name="file" type="file" multiple />
                                    </div>
                                </form>
                                @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                    <button type="button" class="btn btn-sm btn-primary mt-3" data-ai-slider-open
                                        data-dropzone="#my-dropzone" data-hidden-wrap="#sliders"
                                        data-hidden-input-name="slider_images[]"
                                        data-endpoint="{{ route('organizer.ai.generate.slider.images') }}"
                                        data-upload-endpoint="{{ route('organizer.event.imagesstore') }}"
                                        data-remove-endpoint="{{ route('organizer.event.imagermv') }}" data-remove-key="fileid"
                                        {{-- data-max-count="{{ $numberoffImages }}" --}} data-count-default="3" data-size="custom_1170_570">
                                        <i class="fas fa-magic"></i> {{ __('Generate Gallery Images') }}
                                    </button>
                                @endif
                                <div class=" mb-0" id="errpreimg">

                                </div>
                                <p class="text-warning">{{ __('Required: 1170×570. Maximum saved size 1 MB. Larger originals are compressed automatically after crop/resize.') }}</p>
                            </div>
                            <div class="btk-shared-event-wizard" data-event-wizard-actor="organizer" data-event-wizard-mode="create">
<form id="eventForm" data-actor="organizer" data-mode="create" data-event-type="{{ request('type', 'venue') }}" action="{{ route('organizer.event_management.store_event') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="event_type" value="{{ request('type', 'venue') }}">
                                <input type="hidden" name="box_office_enabled" value="{{ request()->boolean('special') ? 1 : 0 }}">
                                @if (request()->boolean('special'))
                                <div class="card border-danger mb-4">
                                  <div class="card-header"><strong>{{ __('Box Office Event — Box Office') }}</strong></div>
                                  <div class="card-body">
                                    <div class="form-group"><label>{{ __('Re-entry Policy') }} *</label>
                                      <select name="reentry_policy" class="form-control">
                                        @foreach (['none'=>'No re-entry','unlimited'=>'Unlimited re-entry','limited'=>'Limited re-entry'] as $value=>$label)
                                          <option value="{{ $value }}" {{ 'none' === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                    <div class="form-group"><label>{{ __('Maximum Re-entries') }}</label>
                                      <input type="number" min="1" name="max_reentries" value="" class="form-control" placeholder="{{ __('Required only for limited re-entry') }}">
                                    </div>
                                    <label><strong>{{ __('Box Office Locations') }} *</strong></label>
                                    <div id="boxOfficeLocations">
                                      @php
                                        $boxLocations = collect([null]);
                                      @endphp
                                      @foreach ($boxLocations as $i => $location)
                                      <div class="row mb-2 box-office-location-row">
                                        <div class="col-md-5"><input class="form-control" name="box_office_locations[{{ $i }}][name]" value="{{ optional($location)->name }}" placeholder="{{ __('Counter name') }}" required></div>
                                        <div class="col-md-6"><input class="form-control" name="box_office_locations[{{ $i }}][address]" value="{{ optional($location)->address }}" placeholder="{{ __('Counter address') }}"></div>
                                        <div class="col-md-1"><button type="button" class="btn btn-danger remove-box-office-location">&times;</button></div>
                                      </div>
                                      @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addBoxOfficeLocation">{{ __('Add Counter') }}</button>
                                  </div>
                                </div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                  const wrap=document.getElementById('boxOfficeLocations'), add=document.getElementById('addBoxOfficeLocation');
                                  if (!wrap || !add) return;
                                  add.addEventListener('click', function(){ const i=wrap.children.length; const row=document.createElement('div'); row.className='row mb-2 box-office-location-row'; row.innerHTML='<div class="col-md-5"><input class="form-control" name="box_office_locations['+i+'][name]" placeholder="Counter name" required></div><div class="col-md-6"><input class="form-control" name="box_office_locations['+i+'][address]" placeholder="Counter address"></div><div class="col-md-1"><button type="button" class="btn btn-danger remove-box-office-location">&times;</button></div>'; wrap.appendChild(row); });
                                  wrap.addEventListener('click', function(e){ if(e.target.closest('.remove-box-office-location') && wrap.children.length>1) e.target.closest('.box-office-location-row').remove(); });
                                });
                                </script>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Thumbnail Image') . '*' }}</label>
                                    <br>
                                    <div class="thumb-preview">
                                        <img id="listingFeaturePreview" src="{{ asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="uploaded-img">
                                    </div>
                                    <input type="hidden" name="thumbnail_image_url" id="listingAiFeatureImage">

                                    <div class="mt-3">
                                        <div role="button" class="btn btn-primary btn-sm upload-btn">
                                            {{ __('Choose Image') }}
                                            <input type="file" class="img-input" name="thumbnail">
                                        </div>
                                        @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                            <button type="button" class="btn btn-primary btn-sm ml-2" data-ai-image-open
                                                data-endpoint="{{ route('organizer.ai.generate.category.image') }}"
                                                data-target="#listingFeaturePreview" data-hidden="#listingAiFeatureImage"
                                                data-file-input="input[name='thumbnail']" data-size="custom_320_230"
                                                data-confirm-text="{{ __('Generate Image') }}">
                                                <i class="fas fa-magic"></i> {{ __('Generate Image') }}
                                            </button>
                                        @endif
                                    </div>
                                    <p class="text-warning">{{ __('Required: 320×230. Maximum saved size 1 MB. Larger originals are compressed automatically after crop/resize.') }}</p>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group mt-1">
                                            <label for="">{{ __('Date Type') . '*' }}</label>
                                            <div class="selectgroup w-100">
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="date_type" value="single"
                                                        class="selectgroup-input eventDateType" checked>
                                                    <span class="selectgroup-button">{{ __('Single') }}</span>
                                                </label>

                                                <label class="selectgroup-item">
                                                    <input type="radio" name="date_type" value="multiple"
                                                        class="selectgroup-input eventDateType">
                                                    <span class="selectgroup-button">{{ __('Multiple') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row countDownStatus">
                                    <div class="col-lg-12">
                                        <div class="form-group mt-1">
                                            <label for="">{{ __('Countdown Status') . '*' }}</label>
                                            <div class="selectgroup w-100">
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="countdown_status" value="1"
                                                        class="selectgroup-input" checked>
                                                    <span class="selectgroup-button">{{ __('Active') }}</span>
                                                </label>

                                                <label class="selectgroup-item">
                                                    <input type="radio" name="countdown_status" value="0"
                                                        class="selectgroup-input">
                                                    <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="single_dates">
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Start Date') . '*' }}</label>
                                            <input type="date" name="start_date" placeholder="Enter Start Date"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label for="">{{ __('Start Time') . '*' }}</label>
                                            <input type="time" name="start_time" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('End Date') . '*' }}</label>
                                            <input type="date" name="end_date" placeholder="Enter End Date"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label for="">{{ __('End Time') . '*' }}</label>
                                            <input type="time" name="end_time" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 d-none" id="multiple_dates">
                                        <div class="form-group">
                                            <table class="table table-bordered ">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Start Date') }}</th>
                                                        <th>{{ __('Start Time') }}</th>
                                                        <th>{{ __('End Date') }}</th>
                                                        <th>{{ __('End Time') }}</th>
                                                        <th><a href="javascrit:void(0)"
                                                                class="btn btn-success addDateRow"><i
                                                                    class="fas fa-plus-circle"></i></a></th>
                                                    </tr>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="">{{ __('Start Date') . '*' }}</label>
                                                                <input type="date" name="m_start_date[]"
                                                                    class="form-control">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="">{{ __('Start Time') . '*' }}</label>
                                                                <input type="time" name="m_start_time[]"
                                                                    class="form-control">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="">{{ __('End Date') . '*' }} </label>
                                                                <input type="date" name="m_end_date[]"
                                                                    class="form-control">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="">{{ __('End Time') . '*' }} </label>
                                                                <input type="time" name="m_end_time[]"
                                                                    class="form-control">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-danger deleteDateRow">
                                                                <i class="fas fa-minus"></i></a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="row ">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Status') . '*' }}</label>
                                            <select name="status" class="form-control">
                                                <option selected disabled>{{ __('Select a Status') }}</option>
                                                <option value="1">{{ __('Active') }}</option>
                                                <option value="0">{{ __('Deactive') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Is Feature') . '*' }}</label>
                                            <select name="is_featured" class="form-control">
                                                <option selected disabled>{{ __('Select') }}</option>
                                                <option value="yes">{{ __('Yes') }}</option>
                                                <option value="no">{{ __('No') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @if (request()->input('type') == 'online')
                                    {{-- /*****--Ticekt limtit & ticket for each customer start--****** --}}

                                    <div class="row">

                                        <div class="col-lg-6">
                                            <div class="form-group mt-1">
                                                <label
                                                    for="">{{ __('Total Number of Available Tickets') . '*' }}</label>
                                                <div class="selectgroup w-100">
                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="ticket_available_type"
                                                            value="unlimited" class="selectgroup-input" checked>
                                                        <span class="selectgroup-button">{{ __('Unlimited') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="ticket_available_type"
                                                            value="limited" class="selectgroup-input">
                                                        <span class="selectgroup-button">{{ __('Limited') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 d-none" id="ticket_available">
                                            <div class="form-group">
                                                <label>{{ __('Enter total number of available tickets') . '*' }}</label>
                                                <input type="number" name="ticket_available"
                                                    placeholder="{{ __('Enter total number of available tickets') }}"
                                                    class="form-control">
                                            </div>
                                        </div>
                                        @if ($websiteInfo->event_guest_checkout_status != 1)
                                            <div class="col-lg-6">
                                                <div class="form-group mt-1">
                                                    <label
                                                        for="">{{ __('Maximum number of tickets for each customer') . '*' }}</label>
                                                    <div class="selectgroup w-100">
                                                        <label class="selectgroup-item">
                                                            <input type="radio" name="max_ticket_buy_type"
                                                                value="unlimited" class="selectgroup-input" checked>
                                                            <span class="selectgroup-button">{{ __('Unlimited') }}</span>
                                                        </label>

                                                        <label class="selectgroup-item">
                                                            <input type="radio" name="max_ticket_buy_type"
                                                                value="limited" class="selectgroup-input">
                                                            <span class="selectgroup-button">{{ __('Limited') }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <input type="hidden" name="max_ticket_buy_type" value="unlimited">
                                        @endif
                                        <div class="col-lg-6 d-none" id="max_buy_ticket">
                                            <div class="form-group">
                                                <label>{{ __('Enter Maximum number of tickets for each customer') . '*' }}</label>
                                                <input type="number" name="max_buy_ticket"
                                                    placeholder="{{ __('Enter Maximum number of tickets for each customer') }}"
                                                    class="form-control">
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="">
                                                <div class="form-group">
                                                    <label for="">{{ __('Price') }}
                                                        ({{ $getCurrencyInfo->text }}) *
                                                    </label>
                                                    <input type="number" name="price" id="ticket-pricing"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter Ticket Price') }}">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" name="pricing_type" value="free" class=""
                                                    id="free_ticket">
                                                <label for="free_ticket">{{ __('Tickets are Free') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="">
                                                <div class="form-group">
                                                    <label for="">{{ __('Meeting Url') }} *
                                                    </label>
                                                    <input type="text" name="meeting_url" class="form-control"
                                                        placeholder="Enter Meeting Url">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="early_bird_discount_free">
                                        <div class="col-lg-12">
                                            <div class="form-group mt-1">
                                                <label for="">{{ __('Early Bird Discount') . '*' }}</label>
                                                <div class="selectgroup w-100">
                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="early_bird_discount_type"
                                                            value="disable" class="selectgroup-input" checked>
                                                        <span class="selectgroup-button">{{ __('Disable') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="early_bird_discount_type"
                                                            value="enable" class="selectgroup-input">
                                                        <span class="selectgroup-button">{{ __('Enable') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 d-none" id="early_bird_dicount">
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount') }} * </label>
                                                        <select name="discount_type" class="form-control">
                                                            <option disabled>{{ __('Select Discount Type') }}</option>
                                                            <option value="fixed">{{ __('Fixed') }}</option>
                                                            <option value="percentage">{{ __('Percentage') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Amount') }} * </label>
                                                        <input type="number" name="early_bird_discount_amount"
                                                            class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount End Date') }} *</label>
                                                        <input type="date" name="early_bird_discount_date"
                                                            class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount End Time') }} *</label>
                                                        <input type="time" name="early_bird_discount_time"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div id="accordion" class="mt-3">
                                    @foreach ($languages as $language)
                                        <div class="version">
                                            <div class="version-header" id="heading{{ $language->id }}">
                                                <h5 class="mb-0">
                                                    <button type="button" class="btn btn-link" data-toggle="collapse"
                                                        data-target="#collapse{{ $language->id }}"
                                                        aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                                        aria-controls="collapse{{ $language->id }}">
                                                        {{ $language->name . ' ' . __('Language') }}
                                                        {{ $language->is_default == 1 ? '(' . __('Default') . ')' : '' }}
                                                    </button>
                                                </h5>
                                            </div>

                                            <div id="collapse{{ $language->id }}"
                                                class="collapse {{ $language->is_default == 1 ? 'show' : '' }}"
                                                aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                                                <div class="version-body">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <div class="listing-ai-inline-row">
                                                                    <label class="mb-0">{{ __('Event Title') . '*' }}</label>
                                                                    @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary listing-ai-field-btn listing-ai-inline-btn"
                                                                            data-field="title" data-lang="{{ $language->code }}"
                                                                            data-title="{{ __('Title') }}">
                                                                            <i class="fas fa-magic"></i> {{ __('Generate') }}
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <input type="text" class="form-control"
                                                                    name="{{ $language->code }}_title"
                                                                    placeholder="{{ __('Enter Event Name') }}">
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-6">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                @php
                                                                    $categories = DB::table('event_categories')
                                                                        ->where('language_id', $language->id)
                                                                        ->where('status', 1)
                                                                        ->orderBy('serial_number', 'asc')
                                                                        ->get();
                                                                @endphp

                                                                <label for="">{{ __('Category') . '*' }}</label>
                                                                <select name="{{ $language->code }}_category_id"
                                                                    class="form-control">
                                                                    <option selected disabled>{{ __('Select Category') }}
                                                                    </option>

                                                                    @foreach ($categories as $category)
                                                                        <option value="{{ $category->id }}">
                                                                            {{ $category->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if (request()->input('type') == 'venue')
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="form-group">
                                                                    <label
                                                                        for="">{{ __('Address') . '*' }}</label>
                                                                    <input type="text"
                                                                        name="{{ $language->code }}_address"
                                                                        id="search-address"
                                                                        class="form-control {{ $language->direction == 1 ? 'rtl text-right' : '' }}"
                                                                        placeholder="{{ __('Enter Address') }}">
                                                                    @if ($language->is_default == 1 && $settings->google_map_status == 1)
                                                                        <a href=""
                                                                            class="btn btn-secondary mt-2 btn-sm"
                                                                            data-toggle="modal"
                                                                            data-target="#GoogleMapModal">
                                                                            <i class="fas fa-eye"></i>
                                                                            {{ __('Show Map') }}
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <!-- latitude and longitude -->
                                                            <input type="hidden" name="latitude" value="{{ @$event->latitude }}" class="latitude">
                                                            <input type="hidden" name="longitude" value="{{ @$event->longitude }}" class="longitude">

                                                            @if ($settings->event_country_status == 1)
                                                                <div class="col-lg-4">
                                                                    <div class="form-group">
                                                                        <label
                                                                            for="">{{ __('Country') . '*' }}</label>
                                                                        <select name="{{ $language->code }}_country"
                                                                            data-lang="{{ $language->id }}"
                                                                            class="form-control country_select countryDropdown">
                                                                            <option selected disabled>
                                                                                {{ __('Select a Country') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if ($settings->event_state_status == 1)
                                                                @php
                                                                    $none = 'none';
                                                                @endphp
                                                                <div class="col-lg-4 state_div"
                                                                    style="display: {{ $settings->event_country_status == 0 ? '' : $none }}">
                                                                    <div class="form-group">
                                                                        <label
                                                                            for="">{{ __('State') . '*' }}</label>
                                                                        <select name="{{ $language->code }}_state"
                                                                            data-lang="{{ $language->id }}"
                                                                            class="form-control stateDropdown state_select">
                                                                            <option selected disabled>
                                                                                {{ __('Select a State') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @php
                                                                $cities = \DB::table('event_cities')
                                                                    ->where([
                                                                        ['language_id', $language->id],
                                                                        ['status', 1],
                                                                    ])
                                                                    ->orderBy('serial_number', 'asc')
                                                                    ->select('id', 'name')
                                                                    ->get();
                                                            @endphp
                                                            <div class="col-lg-4 city_div">
                                                                <div class="form-group">
                                                                    <label for="">{{ __('City') . '*' }}</label>
                                                                    <select name="{{ $language->code }}_city"
                                                                        data-lang="{{ $language->id }}"
                                                                        class="form-control cityDropdown city_select">
                                                                        <option selected disabled>
                                                                            {{ __('Select a City') }}
                                                                        </option>
                                                                        @if ($settings->event_country_status == 0 && $settings->event_state_status == 0)
                                                                            @foreach ($cities as $city)
                                                                                <option value="{{ $city->id }}">
                                                                                    {{ $city->name }}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-4">
                                                                <div class="form-group">
                                                                    <label
                                                                        for="">{{ __('PIN Code') }}</label>
                                                                    <input type="text"
                                                                        placeholder="{{ __('Enter PIN Code') }}"
                                                                        name="{{ $language->code }}_zip_code"
                                                                        class="form-control {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="row">
                                                        <div class="col">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <div class="listing-ai-inline-row">
                                                                    <label class="mb-0">{{ __('Description') . '*' }}</label>
                                                                    @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary listing-ai-field-btn listing-ai-inline-btn"
                                                                            data-field="description"
                                                                            data-lang="{{ $language->code }}"
                                                                            data-title="{{ __('Description') }}">
                                                                            <i class="fas fa-magic"></i> {{ __('Generate') }}
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <textarea id="descriptionTmce{{ $language->id }}" class="form-control btk-simple-description"
                                                                    name="{{ $language->code }}_description" placeholder="{{ __('Enter Event Description') }}" data-height="300" maxlength="1200" rows="8"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Refund Policy') }} *</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_refund_policy" rows="5"
                                                                    placeholder="{{ __('Enter Refund Policy') }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <div class="listing-ai-inline-row">
                                                                    <label class="mb-0">{{ __('Meta Keywords') }}</label>
                                                                    @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary listing-ai-field-btn listing-ai-inline-btn"
                                                                            data-field="meta_keywords"
                                                                            data-lang="{{ $language->code }}"
                                                                            data-title="{{ __('Meta Keywords') }}">
                                                                            <i class="fas fa-magic"></i> {{ __('Generate') }}
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <input class="form-control"
                                                                    name="{{ $language->code }}_meta_keywords"
                                                                    placeholder="{{ __('Enter Meta Keywords') }}"
                                                                    data-role="tagsinput">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <div class="listing-ai-inline-row">
                                                                    <label class="mb-0">{{ __('Meta Description') }}</label>
                                                                    @if ((int) ($settings->ai_system_status ?? 1) === 1)
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary listing-ai-field-btn listing-ai-inline-btn"
                                                                            data-field="meta_description"
                                                                            data-lang="{{ $language->code }}"
                                                                            data-title="{{ __('Meta Description') }}">
                                                                            <i class="fas fa-magic"></i> {{ __('Generate') }}
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <textarea class="form-control" name="{{ $language->code }}_meta_description" rows="5"
                                                                    placeholder="{{ __('Enter Meta Description') }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col">
                                                            @php $currLang = $language; @endphp

                                                            @foreach ($languages as $language)
                                                                @continue($language->id == $currLang->id)

                                                                <div class="form-check py-0">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $language->id }}', event)">
                                                                        <span
                                                                            class="form-check-sign">{{ __('Clone for') }}
                                                                            <strong
                                                                                class="text-capitalize text-secondary">{{ $language->name }}</strong>
                                                                            {{ __('language') }}</span>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="sliders"></div>
                            </form>
</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="EventSubmit" class="btn btn-success">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($settings->google_map_status == 1)
        @includeIf('backend.event.map-modal')
    @endif
@endsection

@section('script')
    @if ($settings->google_map_status == 1)
        <script src="{{ asset('assets/admin/js/event-location.js') }}?v={{ @filemtime(public_path('assets/admin/js/event-location.js')) }}"></script>
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ $settings->google_map_api_key }}&libraries=places&callback=initMap"
            async defer></script>
    @endif
    <script type="text/javascript" src="{{ asset('assets/admin/js/admin-partial.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin_dropzone.js') }}"></script>
    <script src="{{ asset('assets/admin/js/event-wizard.js') }}?v={{ file_exists(public_path('assets/admin/js/event-wizard.js')) ? filemtime(public_path('assets/admin/js/event-wizard.js')) : time() }}"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
        });
    </script>
    <script src="{{ asset('assets/admin/js/event_specification.js') }}"></script>

    @if ((int) ($settings->ai_system_status ?? 1) === 1)
        <script src="{{ asset('assets/admin/js/ai-image-modal.js') }}"></script>
        <script src="{{ asset('assets/admin/js/ai-slider-dropzone.js') }}"></script>
        <script src="{{ asset('assets/admin/js/ai-form-generator.js') }}"></script>
    @endif


    @if ((int) ($settings->ai_system_status ?? 1) === 1)
        <script>
            "use strict";

            AiFormGenerator.init({
                openBtn: '.listing-ai-field-btn',
                modalId: '#aiItemSeoModal',
                modalTitleEl: '#aiItemSeoModalTitle',

                confirmBtn: '#aiItemSeoConfirmBtn',
                endpoint: "{{ route('organizer.ai.generate.content') }}",

                prompt: {
                    from: '#ai_item_prompt'
                },

                hiddenField: '#ai_item_field',
                hiddenLang: '#ai_item_lang',

                extra: {
                    mode: () => 'item_seo',
                    engine: () => $('#ai_engine').val()
                },
                outputs: function() {
                    const outputs = {};

                    (listingAiLanguages || []).forEach(function(language) {
                        const code = language.code;
                        outputs[code + '_title'] = '[name="' + code + '_title"]';
                        outputs[code + '_summary'] = '[name="' + code + '_summary"]';
                        outputs[code + '_description'] = '[name="' + code + '_description"]';
                        outputs[code + '_meta_keywords'] = '[name="' + code + '_meta_keywords"]';
                        outputs[code + '_meta_description'] = '[name="' + code + '_meta_description"]';
                    });

                    return outputs;
                }
            });
        </script>
    @endif
@endsection

@section('variables')
    @php
        $haveCoSt = $settings->event_country_status == 1 && $settings->event_state_status == 1 ? 1 : 0;
        $languages = App\Models\Language::get();
    @endphp
    <script>
        "use strict";
        // let languages = "{{ $languages }}";
        var storeUrl = "{{ route('organizer.event.imagesstore') }}";
        var removeUrl = "{{ route('organizer.event.imagermv') }}";
        var loadImgs = 0;
        const haveCoSt = {{ $haveCoSt }};
        const isActiveState = {{ $settings->event_state_status == 1 ? 1 : 0 }};
        const getStateUrl = "{{ route('organizer.get.city.state') }}";
        const getCityUrl = "{{ route('organizer.get.cities.state') }}";
        var languages = {!! json_encode($languages) !!};
        var listingAiLanguages = {!! $languages->map(function ($language) {
                return ['code' => $language->code];
            })->values()->toJson() !!};
    </script>
@endsection