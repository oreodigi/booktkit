@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">BookTKIT Payment Gateways</h4></div>
<div class="alert alert-info">BookTKIT exposes only the gateways required for India and UK operations. Razorpay is the primary India marketplace gateway; Stripe is reserved for UK/international processing.</div>
<div class="row">
<div class="col-lg-6"><div class="card"><form action="{{ route('admin.payment_gateways.update_razorpay_info') }}" method="post">@csrf
<div class="card-header"><div class="card-title">Razorpay — India / Route</div></div><div class="card-body">
<div class="form-group"><label>Status</label><div class="selectgroup w-100"><label class="selectgroup-item"><input class="selectgroup-input" type="radio" name="razorpay_status" value="1" @checked($razorpay->status==1)><span class="selectgroup-button">Active</span></label><label class="selectgroup-item"><input class="selectgroup-input" type="radio" name="razorpay_status" value="0" @checked($razorpay->status==0)><span class="selectgroup-button">Inactive</span></label></div></div>
@php($rz=json_decode($razorpay->information,true) ?: [])
<div class="form-group"><label>Key ID</label><input class="form-control" name="razorpay_key" value="{{ $rz['key'] ?? '' }}"></div>
<div class="form-group"><label>Key Secret</label><input type="password" class="form-control" name="razorpay_secret" value="{{ $rz['secret'] ?? '' }}"></div>
<div class="form-group"><label>Webhook Secret</label><input type="password" class="form-control" name="razorpay_webhook_secret" value="{{ $rz['webhook_secret'] ?? '' }}"><small>Required for payment/refund/transfer reconciliation.</small></div>
</div><div class="card-footer text-center"><button class="btn btn-success">Save Razorpay</button></div></form></div></div>
<div class="col-lg-6"><div class="card"><form action="{{ route('admin.payment_gateways.update_stripe_info') }}" method="post">@csrf
<div class="card-header"><div class="card-title">Stripe — UK / International</div></div><div class="card-body">
<div class="form-group"><label>Status</label><div class="selectgroup w-100"><label class="selectgroup-item"><input class="selectgroup-input" type="radio" name="stripe_status" value="1" @checked($stripe->status==1)><span class="selectgroup-button">Active</span></label><label class="selectgroup-item"><input class="selectgroup-input" type="radio" name="stripe_status" value="0" @checked($stripe->status==0)><span class="selectgroup-button">Inactive</span></label></div></div>
@php($st=json_decode($stripe->information,true) ?: [])
<div class="form-group"><label>Publishable Key</label><input class="form-control" name="stripe_key" value="{{ $st['key'] ?? '' }}"></div>
<div class="form-group"><label>Secret</label><input type="password" class="form-control" name="stripe_secret" value="{{ $st['secret'] ?? '' }}"></div>
</div><div class="card-footer text-center"><button class="btn btn-success">Save Stripe</button></div></form></div></div>
</div>
@endsection