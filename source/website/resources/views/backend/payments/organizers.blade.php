@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">Organizer Payments</h4></div>
<div class="card"><div class="card-body table-responsive">
<table class="table"><thead><tr><th>Organizer</th><th>Settlement</th><th>Razorpay</th><th>GST</th><th>Platform Fee</th><th>Fee Charged</th><th>Split</th><th></th></tr></thead><tbody>
@forelse($profiles as $p)
<tr><form method="POST" action="{{ route('admin.payments.organizers.update',$p->organizer_id) }}">@csrf
<td>{{ optional($p->organizer)->username ?? ('#'.$p->organizer_id) }}</td>
<td><select name="settlement_mode" class="form-control"><option value="booktkit_managed" @selected($p->settlement_mode==='booktkit_managed')>BookTKIT Managed</option><option value="razorpay_split" @selected($p->settlement_mode==='razorpay_split')>Razorpay Split</option></select></td>
<td><input name="razorpay_account_id" value="{{ $p->razorpay_account_id }}" class="form-control mb-1" placeholder="acc_xxx">
<select name="razorpay_status" class="form-control">@foreach(['not_started','details_submitted','razorpay_pending','kyc_pending','bank_verification_pending','active','restricted','suspended','rejected'] as $s)<option @selected($p->razorpay_status===$s)>{{ $s }}</option>@endforeach</select></td>
<td><input name="gstin" value="{{ $p->gstin }}" class="form-control mb-1" placeholder="GSTIN"><label><input type="checkbox" name="gst_verified" value="1" @checked($p->gst_verified)> Verified</label></td>
<td><select name="fee_type" class="form-control mb-1"><option value="percentage" @selected($p->fee_type==='percentage')>%</option><option value="fixed" @selected($p->fee_type==='fixed')>Fixed</option><option value="hybrid" @selected($p->fee_type==='hybrid')>Hybrid</option></select>
<input name="fee_value" value="{{ $p->fee_value }}" class="form-control mb-1" placeholder="%"><input name="fee_fixed" value="{{ $p->fee_fixed }}" class="form-control" placeholder="Fixed ₹"></td>
<td><select name="fee_bearer" class="form-control"><option value="included" @selected($p->fee_bearer==='included')>Included in ticket</option><option value="additional" @selected($p->fee_bearer==='additional')>Added at checkout</option></select></td>
<td><input type="checkbox" name="split_enabled" value="1" @checked($p->split_enabled)></td><td><button class="btn btn-primary btn-sm">Save</button></td>
</form></tr>
@empty<tr><td colspan="8" class="text-center">No organizer payment profiles yet.</td></tr>@endforelse
</tbody></table>{{ $profiles->links() }}</div></div>
@endsection