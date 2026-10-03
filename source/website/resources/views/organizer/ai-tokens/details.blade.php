@extends('organizer.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Purchase Details') }}</h4>
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
        <a href="{{ route('organizer.ai_token_purchase.history') }}">{{ __('Purchase History') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Details') }}</a>
      </li>
    </ul>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">{{ __('Purchase Details') }}</div>
      <a href="{{ route('organizer.ai_token_purchase.history') }}" class="btn btn-primary btn-sm float-right">{{ __('Back') }}</a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered">
          <tbody>
            <tr>
              <th width="25%">{{ __('Invoice No') }}</th>
              <td>{{ $purchase->invoice_no }}</td>
            </tr>
            <tr>
              <th>{{ __('Package') }}</th>
              <td>{{ $packageTitle }}</td>
            </tr>
            <tr>
              <th>{{ __('AI Engine') }}</th>
              <td class="text-uppercase">{{ $purchase->ai_engine }}</td>
            </tr>
            <tr>
              <th>{{ __('Content Tokens') }}</th>
              <td>{{ $purchase->ai_token_limit }}</td>
            </tr>
            <tr>
              <th>{{ __('Image Limit') }}</th>
              <td>{{ $purchase->ai_image_limit }}</td>
            </tr>
            <tr>
              <th>{{ __('Price') }}</th>
              <td>{{ symbolPrice($purchase->price) }}</td>
            </tr>
            <tr>
              <th>{{ __('Payment Method') }}</th>
              <td>{{ $purchase->payment_method ?? '-' }}</td>
            </tr>
            <tr>
              <th>{{ __('Payment Status') }}</th>
              <td>{{ ucfirst($purchase->payment_status) }}</td>
            </tr>
            <tr>
              <th>{{ __('Approval Status') }}</th>
              <td>{{ ucfirst($purchase->status) }}</td>
            </tr>
            <tr>
              <th>{{ __('Purchased At') }}</th>
              <td>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d M, Y h:i A') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
