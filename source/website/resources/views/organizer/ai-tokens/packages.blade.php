@extends('organizer.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Buy AI Token Package') }}</h4>
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
        <a href="#">{{ __('AI Tokens') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Buy Package') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    @forelse ($packages as $package)
      <div class="col-md-6 col-lg-4">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ $package->title }}</div>
            <p class="card-category mb-0 text-uppercase">{{ $package->ai_engine }}</p>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><strong>{{ __('Content Tokens') }}:</strong> {{ $package->ai_token_limit }}</li>
              <li class="mb-2"><strong>{{ __('Image Limit') }}:</strong> {{ $package->ai_image_limit }}</li>
              <li><strong>{{ __('Price') }}:</strong> {{ symbolPrice($package->price) }}</li>
            </ul>
          </div>
          <div class="card-footer">
            <form action="{{ route('organizer.ai_token_purchase.start_checkout', ['id' => $package->id]) }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-primary btn-block">{{ __('Buy Now') }}</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card">
          <div class="card-body text-center">
            <p class="mb-0">{{ __('No active AI token package is available right now.') }}</p>
          </div>
        </div>
      </div>
    @endforelse
  </div>
@endsection
