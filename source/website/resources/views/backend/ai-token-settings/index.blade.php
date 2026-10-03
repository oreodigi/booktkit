@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('AI Settings') }}</h4>
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
        <a href="#">{{ __('Settings') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('AI Complete System Status') }}</div>
        </div>

        <form action="{{ route('admin.ai_token_settings.update') }}" method="POST">
          @csrf
          <div class="card-body">
            <div class="form-group">
              <label>{{ __('AI System') }}</label>
              <select name="ai_system_status" class="form-control">
                <option value="1" {{ (int) old('ai_system_status', $data->ai_system_status ?? 1) === 1 ? 'selected' : '' }}>
                  {{ __('Enable') }}
                </option>
                <option value="0" {{ (int) old('ai_system_status', $data->ai_system_status ?? 1) === 0 ? 'selected' : '' }}>
                  {{ __('Disable') }}
                </option>
              </select>
              @error('ai_system_status')
                <p class="mt-1 mb-0 text-danger">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-success">{{ __('Update') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

