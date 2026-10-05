@extends('organizer.layout')
@section('content')
<div class="page-header"><h4 class="page-title">Payments & Settlements</h4></div>
<div class="row"><div class="col-md-4"><div class="card"><div class="card-body"><small>Ticket Sales</small><h3>₹{{ number_format($summary['gross']/100,2) }}</h3></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><small>Your Earnings</small><h3>₹{{ number_format($summary['payable']/100,2) }}</h3></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><small>BookTKIT Fees</small><h3>₹{{ number_format($summary['fees']/100,2) }}</h3></div></div></div></div>
<div class="card"><div class="card-header"><div class="card-title">Settlement Setup</div></div><div class="card-body">
<p><strong>Mode:</strong> {{ $profile->settlement_mode==='razorpay_split' ? 'Razorpay Split Settlement' : 'BookTKIT Managed' }}</p>
<p><strong>Razorpay verification:</strong> <span class="badge badge-info">{{ str_replace('_',' ',$profile->razorpay_status) }}</span></p>
<p><strong>GST:</strong> {{ $profile->gst_verified ? 'Verified' : 'Not verified' }} @if($profile->gstin) · {{ $profile->gstin }} @endif</p>
<p><strong>Platform fee:</strong> {{ $profile->fee_type }} {{ $profile->fee_value }}@if($profile->fee_type!=='fixed')%@endif @if($profile->fee_fixed>0) + ₹{{ $profile->fee_fixed }}@endif · {{ $profile->fee_bearer==='additional' ? 'added at checkout' : 'included in ticket price' }}</p>
@if(!$profile->canSplit())<div class="alert alert-warning">Direct split settlement becomes available after BookTKIT verifies your Razorpay linked account and enables settlement.</div>@endif
</div></div>
<div class="card"><div class="card-header"><div class="card-title">Settlement History</div></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>Payment</th><th>Ticket Amount</th><th>Your Amount</th><th>Fee</th><th>Transferred</th><th>Transfer Status</th><th>Hold Release</th><th>Refunds / Reversals</th></tr></thead><tbody>
@forelse($orders as $o)<tr><td>{{ $o->gateway_payment_id ?: $o->uuid }}</td><td>₹{{ number_format($o->ticket_amount/100,2) }}</td><td>₹{{ number_format($o->organizer_amount/100,2) }}</td><td>₹{{ number_format($o->platform_fee/100,2) }}</td>@php $tr=$o->transfers->last(); @endphp<td>₹{{ number_format(($tr->amount ?? 0)/100,2) }}</td><td>{{ $tr->status ?? $o->settlement_mode }}</td><td>{{ $tr?->hold_release_at?->format('d M Y H:i') ?? '—' }}</td><td>Refunded ₹{{ number_format($o->refunded_amount/100,2) }} @if($tr)<br>Reversed ₹{{ number_format(($tr->reversed_amount ?? 0)/100,2) }}@endif</td></tr>@empty<tr><td colspan="8">No payments yet.</td></tr>@endforelse
</tbody></table>{{ $orders->links() }}</div></div>
@endsection