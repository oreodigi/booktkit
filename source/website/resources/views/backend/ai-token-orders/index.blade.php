@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('AI Token Orders') }}</h4>
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
        <a href="#">{{ __('Token Orders') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">{{ __('Token Orders') }}</div>
            </div>

            <div class="col-lg-8">
              <form action="{{ route('admin.ai_token_orders.index') }}" method="GET">
                <div class="row">
                  <div class="col-md-4">
                    <select name="status" class="form-control">
                      <option value="">{{ __('All Status') }}</option>
                      <option value="pending" {{ request()->status == 'pending' ? 'selected' : '' }}>
                        {{ __('Pending') }}
                      </option>
                      <option value="approved" {{ request()->status == 'approved' ? 'selected' : '' }}>
                        {{ __('Approved') }}
                      </option>
                      <option value="rejected" {{ request()->status == 'rejected' ? 'selected' : '' }}>
                        {{ __('Rejected') }}
                      </option>
                    </select>
                  </div>

                  <div class="col-md-4">
                    <select name="payment_status" class="form-control">
                      <option value="">{{ __('All Payment Status') }}</option>
                      <option value="pending" {{ request()->payment_status == 'pending' ? 'selected' : '' }}>
                        {{ __('Pending') }}
                      </option>
                      <option value="paid" {{ request()->payment_status == 'paid' ? 'selected' : '' }}>
                        {{ __('Paid') }}
                      </option>
                      <option value="failed" {{ request()->payment_status == 'failed' ? 'selected' : '' }}>
                        {{ __('Failed') }}
                      </option>
                    </select>
                  </div>

                  <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                    <a href="{{ route('admin.ai_token_orders.index') }}" class="btn btn-secondary btn-sm">
                      {{ __('Reset') }}
                    </a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if ($orders->count() == 0)
                <h3 class="text-center">{{ __('NO TOKEN ORDER FOUND') . '!' }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('Transaction ID') }}</th>
                        <th scope="col">{{ __('Organizer ID') }}</th>
                        <th scope="col">{{ __('AI Engine') }}</th>
                        <th scope="col">{{ __('Token Limit') }}</th>
                        <th scope="col">{{ __('Image Limit') }}</th>
                        <th scope="col">{{ __('Price') }}</th>
                        <th scope="col">{{ __('Payment Proof') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Payment Status') }}</th>
                        <th scope="col">{{ __('Offline Payment Control') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($orders as $order)
                        <tr>
                          <td>{{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                          <td>{{ $order->invoice_no ?? '-' }}</td>
                          <td>{{ $order->organizer_id }}</td>
                          <td>{{ strtoupper($order->ai_engine) }}</td>
                          <td>{{ $order->ai_token_limit }}</td>
                          <td>{{ $order->ai_image_limit }}</td>
                          <td>{{ adminDefaultCurrency($order->price) }}</td>
                          <td>
                            @if (!empty($order->image))
                              <a href="{{ asset('assets/admin/file/organizer-ai-token/proofs/' . $order->image) }}"
                                target="_blank">
                                <img
                                  src="{{ asset('assets/admin/file/organizer-ai-token/proofs/' . $order->image) }}"
                                  alt="payment-proof" width="60" class="img-thumbnail">
                              </a>
                            @else
                              <span>-</span>
                            @endif
                          </td>
                          <td>
                            @php
                              $statusClass = match ($order->status) {
                                  'approved' => 'bg-success text-white border-success',
                                  'rejected' => 'bg-danger text-white border-danger',
                                  default => 'bg-warning text-dark border-warning',
                              };
                            @endphp
                            <form action="{{ route('admin.ai_token_orders.update_status', ['id' => $order->id]) }}"
                              method="POST">
                              @csrf
                              <select name="status" class="form-control form-control-sm {{ $statusClass }}"
                                onchange="this.form.submit()">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>
                                  {{ __('Pending') }}
                                </option>
                                <option value="approved" {{ $order->status == 'approved' ? 'selected' : '' }}>
                                  {{ __('Approved') }}
                                </option>
                                <option value="rejected" {{ $order->status == 'rejected' ? 'selected' : '' }}>
                                  {{ __('Rejected') }}
                                </option>
                              </select>
                            </form>
                          </td>
                          <td>
                            @if ($order->payment_status == 'paid')
                              <span class="badge badge-success">{{ __('Paid') }}</span>
                            @elseif ($order->payment_status == 'failed')
                              <span class="badge badge-danger">{{ __('Failed') }}</span>
                            @else
                              <span class="badge badge-warning">{{ __('Pending') }}</span>
                            @endif
                          </td>
                          <td>
                            @if ($order->payment_method === 'offline')
                              @php
                                $paymentStatusClass = match ($order->payment_status) {
                                    'paid' => 'bg-success text-white border-success',
                                    'failed' => 'bg-danger text-white border-danger',
                                    default => 'bg-warning text-dark border-warning',
                                };
                              @endphp
                              <form action="{{ route('admin.ai_token_orders.update_payment_status', ['id' => $order->id]) }}"
                                method="POST">
                                @csrf
                                <select name="payment_status" class="form-control form-control-sm {{ $paymentStatusClass }}"
                                  onchange="this.form.submit()">
                                  <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>
                                    {{ __('Pending') }}
                                  </option>
                                  <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>
                                    {{ __('Paid') }}
                                  </option>
                                  <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>
                                    {{ __('Failed') }}
                                  </option>
                                </select>
                              </form>
                            @else
                              <span>N/A</span>
                            @endif
                          </td>
                          <td>
                            <a href="{{ route('admin.ai_token_orders.show', ['id' => $order->id]) }}"
                              class="btn btn-info mt-1 btn-xs">
                              <span class="btn-label">
                                <i class="fas fa-eye"></i>
                              </span>
                            </a>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

                <div class="mt-3 d-flex justify-content-center">
                  {{ $orders->appends(request()->input())->links() }}
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="card-footer"></div>
      </div>
    </div>
  </div>
@endsection
