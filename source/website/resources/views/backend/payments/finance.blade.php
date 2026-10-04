@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">BookTKIT Finance</h4></div>
<div class="row">
 @foreach(['paid'=>'Customer Payments','platform'=>'Platform Revenue','organizer'=>'Organizer Payable','refunded'=>'Refunded'] as $key=>$label)
 <div class="col-md-3"><div class="card"><div class="card-body"><small>{{ $label }}</small><h3>₹{{ number_format($summary[$key]/100,2) }}</h3></div></div></div>
 @endforeach
</div>
<div class="card"><div class="card-header"><div class="card-title">Payment Orders & Settlement</div></div><div class="card-body table-responsive">
<table class="table table-striped"><thead><tr><th>Order</th><th>Booking</th><th>Organizer</th><th>Customer Paid</th><th>Platform Fee</th><th>Organizer</th><th>Status</th><th>Settlement</th><th>Refund</th></tr></thead><tbody>
@forelse($orders as $o)<tr><td><small>{{ $o->uuid }}</small></td><td>{{ $o->booking_id ?: 'Pending' }}</td><td>{{ $o->organizer_id ?: 'BookTKIT' }}</td>
<td>₹{{ number_format($o->customer_total/100,2) }}</td><td>₹{{ number_format($o->platform_fee/100,2) }}</td><td>₹{{ number_format($o->organizer_amount/100,2) }}</td>
<td><span class="badge badge-info">{{ $o->status }}</span></td><td>{{ $o->settlement_mode }} @if($o->transfers->count()) / {{ $o->transfers->last()->status }} @endif</td>
<td>{{ $o->refund_status }} @if($o->refunded_amount) ₹{{ number_format($o->refunded_amount/100,2) }} @endif</td></tr>
@empty<tr><td colspan="9" class="text-center">No BookTKIT payment orders yet.</td></tr>@endforelse
</tbody></table>{{ $orders->links() }}</div></div>
@endsection