@extends('organizer.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('AI Token Purchase History') }}</h4>
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
                <a href="#">{{ __('Purchase History') }}</a>
            </li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">{{ __('Purchase History') }}</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Transaction ID') }}</th>
                            <th>{{ __('Package') }}</th>
                            <th>{{ __('Engine') }}</th>
                            <th>{{ __('Tokens') }}</th>
                            <th>{{ __('Images') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Payment Status') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->invoice_no }}</td>
                                <td>{{ $purchase->package_title ?? 'Package #' . $purchase->ai_token_package_id }}</td>
                                <td class="text-uppercase">{{ $purchase->ai_engine }}</td>
                                <td>{{ $purchase->ai_token_limit }}</td>
                                <td>{{ $purchase->ai_image_limit }}</td>
                                <td>{{ symbolPrice($purchase->price) }}</td>
                                <td>{{ $purchase->payment_method ?? '-' }}</td>
                                <td><span
                                        class="badge badge-{{ $purchase->payment_status == 'paid' ? 'success' : ($purchase->payment_status == 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($purchase->payment_status) }}</span>
                                </td>
                                <td><span
                                        class="badge badge-{{ $purchase->status == 'approved' ? 'success' : ($purchase->status == 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($purchase->status) }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d M, Y h:i A') }}</td>
                                <td>
                                    <a href="{{ route('organizer.ai_token_purchase.details', ['id' => $purchase->id]) }}"
                                        class="btn btn-sm btn-primary">{{ __('Details') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">{{ __('No purchase history found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $purchases->links() }}
            </div>
        </div>
    </div>
@endsection
