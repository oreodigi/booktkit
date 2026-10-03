@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Edit Token Package') }}</h4>
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
        <a href="#">{{ __('AI Token Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="{{ route('admin.ai_token_packages.index') }}">{{ __('Token Packages') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Edit Token Package') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Edit Token Package') }}</div>
          <a href="{{ route('admin.ai_token_packages.index') }}" class="btn btn-info btn-sm float-right">
            <span class="btn-label">
              <i class="fas fa-backward"></i>
            </span>
            {{ __('Back') }}
          </a>
        </div>

        <form action="{{ route('admin.ai_token_packages.update', ['id' => $package->id]) }}" method="POST">
          @csrf
          <div class="card-body">
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Title') . '*' }}</label>
                  <input type="text" name="title" class="form-control"
                    value="{{ old('title', $package->title) }}" placeholder="{{ __('Enter title') }}">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('AI Engine') . '*' }}</label>
                  <select name="ai_engine" class="form-control">
                    <option value="openai"
                      {{ old('ai_engine', $package->ai_engine) == 'openai' ? 'selected' : '' }}>OpenAI</option>
                    <option value="gemini"
                      {{ old('ai_engine', $package->ai_engine) == 'gemini' ? 'selected' : '' }}>Gemini</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('AI Token Limit') . '*' }}</label>
                  <input type="number" min="0" name="ai_token_limit" class="form-control"
                    value="{{ old('ai_token_limit', $package->ai_token_limit) }}"
                    placeholder="{{ __('Enter token limit') }}">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('AI Image Limit') . '*' }}</label>
                  <input type="number" min="0" name="ai_image_limit" class="form-control"
                    value="{{ old('ai_image_limit', $package->ai_image_limit) }}"
                    placeholder="{{ __('Enter image limit') }}">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Price') . '*' }}</label>
                  <input type="number" min="0" step="0.01" name="price" class="form-control"
                    value="{{ old('price', $package->price) }}" placeholder="{{ __('Enter price') }}">
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Status') . '*' }}</label>
                  <select name="status" class="form-control">
                    <option value="1" {{ old('status', $package->status) == 1 ? 'selected' : '' }}>
                      {{ __('Active') }}
                    </option>
                    <option value="0" {{ old('status', $package->status) == 0 ? 'selected' : '' }}>
                      {{ __('Inactive') }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer text-center">
            <button type="submit" class="btn btn-success">{{ __('Update') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
