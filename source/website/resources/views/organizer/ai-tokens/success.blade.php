@extends('organizer.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Purchase Submitted') }}</h4>
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
        <a href="#">{{ __('Success') }}</a>
      </li>
    </ul>
  </div>

  <div class="card">
    <div class="card-body text-center">
      <h4 class="mb-3">{{ __('Your AI token package request has been recorded') . '.' }}</h4>
      <p class="mb-1"><strong>{{ __('Transaction ID') }}:</strong> {{ $purchase->invoice_no }}</p>
      <p class="mb-1"><strong>{{ __('Package') }}:</strong> {{ $packageTitle }}</p>
      <p class="mb-1"><strong>{{ __('Payment Status') }}:</strong> {{ ucfirst($purchase->payment_status) }}</p>
      <p class="mb-4"><strong>{{ __('Approval Status') }}:</strong> {{ ucfirst($purchase->status) }}</p>

      <a href="{{ route('organizer.ai_token_purchase.history') }}" class="btn btn-primary mr-2">{{ __('View History') }}</a>
      <a href="{{ route('organizer.ai_token_purchase.packages') }}" class="btn btn-secondary">{{ __('Buy Another Package') }}</a>
    </div>
  </div>
@endsection
