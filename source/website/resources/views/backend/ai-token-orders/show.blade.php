@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Token Order Details') }}</h4>
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
        <a href="{{ route('admin.ai_token_orders.index') }}">{{ __('Token Orders') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Details') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Order Information') }}</div>
          <a href="{{ route('admin.ai_token_orders.index') }}" class="btn btn-info btn-sm float-right">
            <span class="btn-label">
              <i class="fas fa-backward"></i>
            </span>
            {{ __('Back') }}
          </a>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th width="30%">{{ __('Transaction ID') }}</th>
                  <td>{{ $order->invoice_no ?? '-' }}</td>
                </tr>
                <tr>
                  <th>{{ __('Organizer ID') }}</th>
                  <td>{{ $order->organizer_id }}</td>
                </tr>
                <tr>
                  <th>{{ __('Package ID') }}</th>
                  <td>{{ $order->ai_token_package_id }}</td>
                </tr>
                <tr>
                  <th>{{ __('AI Engine') }}</th>
                  <td>{{ strtoupper($order->ai_engine) }}</td>
                </tr>
                <tr>
                  <th>{{ __('AI Token Limit') }}</th>
                  <td>{{ $order->ai_token_limit }}</td>
                </tr>
                <tr>
                  <th>{{ __('AI Image Limit') }}</th>
                  <td>{{ $order->ai_image_limit }}</td>
                </tr>
                <tr>
                  <th>{{ __('Price') }}</th>
                  <td>{{ adminDefaultCurrency($order->price) }}</td>
                </tr>
                <tr>
                  <th>{{ __('Payment Method') }}</th>
                  <td>{{ $order->payment_method ?? '-' }}</td>
                </tr>

                <tr>
                  <th>{{ __('Payment Status') }}</th>
                  <td>{{ ucfirst($order->payment_status) }}</td>
                </tr>
                <tr>
                  <th>{{ __('Created At') }}</th>
                  <td>{{ $order->created_at }}</td>
                </tr>
                <tr>
                  <th>{{ __('Updated At') }}</th>
                  <td>{{ $order->updated_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
