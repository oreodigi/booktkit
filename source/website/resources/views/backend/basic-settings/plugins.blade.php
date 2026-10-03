@extends('backend.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Plugins') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Basic Settings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Plugins') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.geo') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Google Map') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Google Map Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_map_status" value="1"
                                                class="selectgroup-input"
                                                {{ $data->google_map_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_map_status" value="0"
                                                class="selectgroup-input"
                                                {{ $data->google_map_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('google_map_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_map_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Google Map Api Key') . '*' }}</label>
                                    <input type="text" class="form-control" name="google_map_api_key"
                                        value="{{ $data->google_map_api_key }}">

                                    @if ($errors->has('google_map_api_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_map_api_key') }}</p>
                                    @endif
                                    <h6 class="mt-2 mb-1 text-warning">{{ __('If the Google Maps is disabled:') }}</h6>
                                    <ul class="pl-20 mb-0">
                                        <li>
                                            <p class="mb-0 text-warning">
                                                {{ __('Address suggestions will not be available in the location search input.') }}
                                            </p>
                                        </li>
                                        <li>
                                            <p class="mb-0 text-warning">
                                                {{ __('Google will not suggest locations under address inputs.') }}</p>
                                        </li>
                                        <li>
                                            <p class="mb-0 text-warning">
                                                {{ __('Radius-base searchings will be disabled.') }}
                                            </p>
                                        </li>
                                    </ul>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Radius') . '*' }}({{ __('meters') }})</label>
                                    <input type="text" class="form-control" name="google_map_radius"
                                        value="{{ $data->google_map_radius }}">

                                    @if ($errors->has('google_map_radius'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_map_radius') }}</p>
                                    @endif
                                    <p class="mb-0 text-warning">
                                        {{ __('After a location is seached, all the available services which are located within this radius will be displayed.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_disqus') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Disqus') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Disqus Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="disqus_status" value="1"
                                                class="selectgroup-input" {{ $data->disqus_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="disqus_status" value="0"
                                                class="selectgroup-input" {{ $data->disqus_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('disqus_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('disqus_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Disqus Short Name') . '*' }}</label>
                                    <input type="text" class="form-control" name="disqus_short_name"
                                        value="{{ $data->disqus_short_name }}">

                                    @if ($errors->has('disqus_short_name'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('disqus_short_name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_recaptcha') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Google Recaptcha') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Recaptcha Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_recaptcha_status" value="1"
                                                class="selectgroup-input"
                                                {{ $data->google_recaptcha_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_recaptcha_status" value="0"
                                                class="selectgroup-input"
                                                {{ $data->google_recaptcha_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('google_recaptcha_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_recaptcha_status') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Site Key') . '*' }}</label>
                                    <input type="text" class="form-control" name="google_recaptcha_site_key"
                                        value="{{ $data->google_recaptcha_site_key }}">

                                    @if ($errors->has('google_recaptcha_site_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_recaptcha_site_key') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Secret Key') . '*' }}</label>
                                    <input type="text" class="form-control" name="google_recaptcha_secret_key"
                                        value="{{ $data->google_recaptcha_secret_key }}">

                                    @if ($errors->has('google_recaptcha_secret_key'))
                                        <p class="mt-1 mb-0 text-danger">
                                            {{ $errors->first('google_recaptcha_secret_key') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_facebook') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Login via Facebook') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Login Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="facebook_login_status" value="1"
                                                class="selectgroup-input"
                                                {{ !empty($data) && $data->facebook_login_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Enable') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="facebook_login_status" value="0"
                                                class="selectgroup-input"
                                                {{ !empty($data) && $data->facebook_login_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Disable') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('facebook_login_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('facebook_login_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('App ID') . '*' }}</label>
                                    <input type="text" class="form-control" name="facebook_app_id"
                                        value="{{ !empty($data) ? $data->facebook_app_id : '' }}">

                                    @if ($errors->has('facebook_app_id'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('facebook_app_id') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('App Secret') . '*' }}</label>
                                    <input type="text" class="form-control" name="facebook_app_secret"
                                        value="{{ !empty($data) ? $data->facebook_app_secret : '' }}">

                                    @if ($errors->has('facebook_app_secret'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('facebook_app_secret') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_google') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Login via Google') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Login Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_login_status" value="1"
                                                class="selectgroup-input"
                                                {{ !empty($data) && $data->google_login_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Enable') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="google_login_status" value="0"
                                                class="selectgroup-input"
                                                {{ !empty($data) && $data->google_login_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Disable') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('google_login_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_login_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Client ID') . '*' }}</label>
                                    <input type="text" class="form-control" name="google_client_id"
                                        value="{{ !empty($data) ? $data->google_client_id : '' }}">

                                    @if ($errors->has('google_client_id'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_client_id') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Client Secret') . '*' }}</label>
                                    <input type="text" class="form-control" name="google_client_secret"
                                        value="{{ !empty($data) ? $data->google_client_secret : '' }}">

                                    @if ($errors->has('google_client_secret'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_client_secret') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_openai') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="card-title">{{ __('OpenAI') }}</div>
                    </div>

                    <div class="card-body">

                        <div class="form-group">
                            <label>{{ __('OpenAI API Key') }}</label>
                            <input type="text" class="form-control" name="openai_api_key"
                                value="{{ $data->openai_api_key }}" placeholder="sk-projXXXXXXXX">
                            @if ($errors->has('openai_api_key'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('openai_api_key') }}</p>
                            @endif
                        </div>
                        {{-- Text Model --}}
                        <div class="form-group js-model js-openai-text">
                            <label>{{ __('OpenAI Text Model') }}</label>
                            <input type="text" class="form-control" name="openai_text_model"
                                value="{{ $data->openai_text_model ?? 'gpt-4o' }}" placeholder="gpt-4o">
                            @if ($errors->has('openai_text_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('openai_text_model') }}</p>
                            @endif
                            <small class="text-warning">
                                {{ __('Examples') . ': ' . __('gpt-4o-mini, gpt-4o, gpt-4.1, gpt-3.5-turbo') }}
                            </small>
                        </div>

                        {{-- Image Model --}}
                        <div class="form-group js-model js-openai-image">
                            <label>{{ __('OpenAI Image Model') }}</label>
                            <input type="text" class="form-control" name="openai_image_model"
                                value="{{ $data->openai_image_model ?? 'dall-e-3' }}" placeholder="dall-e-3">
                            @if ($errors->has('openai_image_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('openai_image_model') }}</p>
                            @endif
                            <small class="text-warning">
                                {{ __('Examples') . ': ' . __('gpt-image-1, dall-e-3, dall-e-2') }}
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_gemini') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="card-title">{{ __('Google Gemini') }}</div>
                    </div>

                    <div class="card-body">

                        {{-- API Key --}}
                        <div class="form-group">
                            <label>{{ __('Gemini API Key') }}</label>
                            <input type="text" class="form-control" name="gemini_api_key"
                                value="{{ $data->gemini_api_key }}" placeholder="AIzaSyXXXXXXXX">
                            @if ($errors->has('gemini_api_key'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('gemini_api_key') }}</p>
                            @endif
                        </div>

                        {{-- Text Model --}}
                        <div class="form-group js-model js-gemini-text">
                            <label>{{ __('Gemini Text Model') }}</label>
                            <input type="text" class="form-control" name="gemini_text_model"
                                value="{{ $data->gemini_text_model ?? 'gemini-2.0-flash' }}"
                                placeholder="gemini-2.0-flash">
                            @if ($errors->has('gemini_text_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('gemini_text_model') }}</p>
                            @endif
                            <small
                                class="text-warning">{{ __('Examples') . ': ' . __('gemini-1.5-flash, gemini-1.5-pro, gemini-2.0-flash') }}</small>
                        </div>

                        {{-- Image Model --}}
                        <div class="form-group js-model js-gemini-image">
                            <label>{{ __('Gemini Image Model') }}</label>
                            <input type="text" class="form-control" name="gemini_image_model"
                                value="{{ $data->gemini_image_model ?? 'imagen-4.0-generat-001' }}"
                                placeholder="imagen-4.0-generat-001">
                            @if ($errors->has('gemini_image_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('gemini_image_model') }}</p>
                            @endif
                            <small
                                class="text-warning">{{ __('Examples') . ': ' . __('imagen-4.0-generat-001, imagen-4.0-fast-generate-001') }}</small>

                            <br>

                            <small class="text-warning">
                                {{ __('Gemini Image models must include the version suffix (-001)') . '. ' . __('Without the suffix, the API will return a 404 model not found error') }}
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.update_pollinations') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="card-title">{{ __('Pollinations AI') }}</div>
                    </div>
                    <div class="card-body">

                        <div class="form-group">
                            <label>{{ __('Pollinations Secret Key') }}</label>
                            <input type="text" class="form-control" name="pollinations_secret_key"
                                value="{{ $data->pollinations_secret_key }}" placeholder="sk-projXXXXXXXX">
                            @if ($errors->has('pollinations_secret_key'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('pollinations_secret_key') }}</p>
                            @endif
                        </div>

                        <div class="form-group js-model js-pollinations-text">
                            <label>{{ __('Pollinations Text Model') }}</label>
                            <input type="text" class="form-control" name="pollinations_text_model"
                                value="{{ $data->pollinations_text_model ?? 'gemini-fast' }}" placeholder="gemini-fast">
                            @if ($errors->has('pollinations_text_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('pollinations_text_model') }}</p>
                            @endif
                            <small class="text-warning">
                                {{ __('Examples') . ': ' . __('qwen-character, nova-fast, gemini-fast, gemini-search') }}
                            </small>
                        </div>


                        <div class="form-group js-model js-pollinations-image">
                            <label>{{ __('Pollinations Image Model') }}</label>
                            <input type="text" class="form-control" name="pollinations_image_model"
                                value="{{ $data->pollinations_image_model ?? 'flux' }}" placeholder="flux">
                            @if ($errors->has('pollinations_image_model'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('pollinations_image_model') }}</p>
                            @endif
                            <small class="text-warning">{{ __('Examples') . ': ' . __('flux, zimage') }}</small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div> --}}

    </div>
@endsection
