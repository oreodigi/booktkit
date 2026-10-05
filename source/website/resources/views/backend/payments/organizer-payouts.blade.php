@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">Organizer Payouts</h4></div>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<div class="card"><div class="card-body">
<form class="form-inline mb-3"><select name="status" class="form-control mr-2"><option value="">All KYC statuses</option>@foreach(['draft','submitted','under_review','needs_clarification','activated','rejected','suspended'] as $s)<option value="{{$s}}" @selected(request('status')===$s)>{{ucwords(str_replace('_',' ',$s))}}</option>@endforeach</select><button class="btn btn-primary">Filter</button></form>
<form method="POST" action="{{route('admin.organizer_payouts.hold_days')}}" class="form-inline mb-3">@csrf<label class="mr-2">Transfer hold days</label><input type="number" min="0" max="90" name="transfer_hold_days" value="{{$holdDays}}" class="form-control mr-2"><button class="btn btn-secondary">Save</button></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Organizer</th><th>KYC</th><th>Razorpay IDs</th><th>Masked KYC</th><th>Fee</th><th>Actions</th></tr></thead><tbody>
@foreach($profiles as $p)<tr><td>{{$p->organizer->username??$p->organizer_id}}</td><td><span class="badge badge-info">{{str_replace('_',' ',$p->kyc_status)}}</span>@if($p->kyc_remarks)<div class="small mt-1">{{json_encode($p->kyc_remarks)}}</div>@endif</td><td><small>{{$p->razorpay_account_id}}<br>{{$p->razorpay_product_id}}</small></td><td>{{$p->maskedPan()}}<br>{{$p->maskedGstin()}}<br>{{$p->maskedBankAccount()}}</td><td>{{$p->fee_type}} {{$p->fee_value}} / {{$p->fee_fixed}} · {{$p->fee_bearer}}</td><td><form method="POST" action="{{route('admin.organizer_payouts.sync',$p->id)}}">@csrf<button class="btn btn-sm btn-primary">Sync</button></form></td></tr>@endforeach
</tbody></table></div>{{$profiles->links()}}</div></div>
<a href="{{route('admin.organizer_payouts.transfers')}}" class="btn btn-primary">View Transfers</a>
@endsection