@extends('organizer.layout')
@section('content')
@php
  $status = $profile->kyc_status ?: 'draft';
  $statusCopy = [
    'draft' => ['secondary','Payout setup is incomplete. Complete all steps and submit.'],
    'submitted' => ['info','Your details were submitted and are waiting to be sent for verification.'],
    'under_review' => ['info','Razorpay is reviewing your payout setup.'],
    'needs_clarification' => ['warning','More information is required. Review the remarks below, correct the details and resubmit.'],
    'activated' => ['success','Payout setup is active. Ticket settlements can use your linked account.'],
    'rejected' => ['danger','Payout verification was rejected. Review the remarks and contact support if required.'],
    'suspended' => ['danger','Split payouts are suspended. Contact BookTKIT support.'],
  ];
  [$badge,$message] = $statusCopy[$status] ?? ['secondary','Payout setup status is unavailable.'];
  $locked = in_array($status, ['activated','suspended'], true);
@endphp
<div class="page-header"><h4 class="page-title">{{ __('Payouts & KYC') }}</h4></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please correct the highlighted details.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="card mb-4"><div class="card-body d-flex justify-content-between align-items-center flex-wrap">
  <div><div class="text-uppercase small text-muted">KYC status</div><h4 class="mb-1">{{ ucwords(str_replace('_',' ',$status)) }}</h4><div>{{ $message }}</div></div>
  <span class="badge badge-{{ $badge }} p-2">{{ strtoupper(str_replace('_',' ',$status)) }}</span>
</div></div>

@if($status === 'needs_clarification' && !empty($profile->kyc_remarks))
<div class="alert alert-warning"><strong>Verification remarks</strong><ul class="mb-0">@foreach((array)$profile->kyc_remarks as $key=>$remark)<li>{{ is_string($key) ? ucwords(str_replace('_',' ',$key)).': ' : '' }}{{ is_scalar($remark) ? $remark : json_encode($remark) }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('organizer.payouts.kyc.save') }}" autocomplete="off">@csrf
<div class="card"><div class="card-header"><div class="card-title">1. Business details</div></div><div class="card-body"><div class="row">
@php $types=['individual'=>'Individual','proprietorship'=>'Proprietorship','partnership'=>'Partnership','private_limited'=>'Private Limited','public_limited'=>'Public Limited','llp'=>'LLP','trust'=>'Trust','society'=>'Society','ngo'=>'NGO']; @endphp
<div class="col-md-4 form-group"><label>Business type *</label><select class="form-control" name="business_type" required @disabled($locked)>@foreach($types as $v=>$l)<option value="{{ $v }}" @selected(old('business_type',$profile->business_type)===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Legal business name *</label><input class="form-control" name="legal_business_name" value="{{ old('legal_business_name',$profile->legal_business_name) }}" required @disabled($locked)></div>
<div class="col-md-4 form-group"><label>Brand name</label><input class="form-control" name="brand_name" value="{{ old('brand_name',$profile->brand_name) }}" @disabled($locked)></div>
<div class="col-md-4 form-group"><label>Contact name *</label><input class="form-control" name="contact_name" value="{{ old('contact_name',$profile->contact_name) }}" required @disabled($locked)></div>
<div class="col-md-4 form-group"><label>Contact email *</label><input type="email" class="form-control" name="contact_email" value="{{ old('contact_email',$profile->contact_email) }}" required @disabled($locked)></div>
<div class="col-md-4 form-group"><label>Contact phone *</label><input class="form-control" name="contact_phone" value="{{ old('contact_phone',$profile->contact_phone) }}" required @disabled($locked)></div>
<div class="col-md-6 form-group"><label>Address line 1 *</label><input class="form-control" name="address_line1" value="{{ old('address_line1',$profile->address_line1) }}" required @disabled($locked)></div>
<div class="col-md-6 form-group"><label>Address line 2</label><input class="form-control" name="address_line2" value="{{ old('address_line2',$profile->address_line2) }}" @disabled($locked)></div>
<div class="col-md-3 form-group"><label>City *</label><input class="form-control" name="address_city" value="{{ old('address_city',$profile->address_city) }}" required @disabled($locked)></div>
<div class="col-md-3 form-group"><label>State *</label><input class="form-control" name="address_state" value="{{ old('address_state',$profile->address_state) }}" required @disabled($locked)></div>
<div class="col-md-3 form-group"><label>Postcode *</label><input class="form-control" name="address_postcode" value="{{ old('address_postcode',$profile->address_postcode) }}" required @disabled($locked)></div>
<div class="col-md-3 form-group"><label>Country *</label><input class="form-control" name="address_country" value="{{ old('address_country',$profile->address_country ?: 'IN') }}" maxlength="2" required @disabled($locked)></div>
<div class="col-md-6 form-group"><label>Business category *</label><input class="form-control" name="business_category" value="{{ old('business_category',$profile->business_category) }}" required @disabled($locked)></div>
<div class="col-md-6 form-group"><label>Business subcategory *</label><input class="form-control" name="business_subcategory" value="{{ old('business_subcategory',$profile->business_subcategory) }}" required @disabled($locked)></div>
</div></div></div>

<div class="card"><div class="card-header"><div class="card-title">2. Tax details</div></div><div class="card-body"><div class="row">
<div class="col-md-4 form-group"><label>Business PAN *</label><input class="form-control text-uppercase" name="pan" maxlength="10" value="{{ old('pan') }}" placeholder="{{ $profile->maskedPan() ?: 'AAAAA9999A' }}" @disabled($locked)><small class="text-muted">Stored encrypted. Existing value is masked; enter the full PAN only when changing it.</small></div>
<div class="col-md-4 form-group"><label>Stakeholder / person PAN</label><input class="form-control text-uppercase" name="stakeholder_pan" maxlength="10" value="{{ old('stakeholder_pan') }}" placeholder="{{ $profile->maskedStakeholderPan() ?: 'Required for incorporated/entity types' }}" @disabled($locked)></div>
<div class="col-md-4 form-group"><label>GSTIN (optional)</label><input class="form-control text-uppercase" name="gstin" maxlength="15" value="{{ old('gstin') }}" placeholder="{{ $profile->maskedGstin() ?: '' }}" @disabled($locked)><small class="text-muted">GSTIN PAN segment must match the business PAN.</small></div>
</div></div></div>

<div class="card"><div class="card-header"><div class="card-title">3. Bank account</div></div><div class="card-body"><div class="row">
<div class="col-md-4 form-group"><label>Account holder *</label><input class="form-control" name="bank_account_holder" value="{{ old('bank_account_holder',$profile->bank_account_holder) }}" required @disabled($locked)></div>
<div class="col-md-4 form-group"><label>IFSC *</label><input class="form-control text-uppercase" name="bank_ifsc" value="{{ old('bank_ifsc',$profile->bank_ifsc) }}" required @disabled($locked)></div>
<div class="col-md-4 form-group"><label>Bank account number {{ $profile->bank_account_last4 ? '' : '*' }}</label><input type="password" inputmode="numeric" class="form-control" name="bank_account_number" value="" placeholder="{{ $profile->maskedBankAccount() ?: 'Enter account number' }}" @disabled($locked)><small class="text-muted">BookTKIT never stores the full account number. @if($profile->bank_account_last4)Saved account: {{ $profile->maskedBankAccount() }}@endif</small></div>
</div></div></div>

<div class="card"><div class="card-header"><div class="card-title">4. Review & submit</div></div><div class="card-body">
<p class="mb-3">Confirm the legal, tax and settlement details above are accurate. Sensitive identifiers are encrypted or stored only as masked references.</p>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" name="route_terms_accepted" id="route_terms_accepted"><label class="form-check-label" for="route_terms_accepted">I consent to BookTKIT sharing these KYC and settlement details with Razorpay for Route onboarding and split settlements, and I accept the applicable Razorpay terms.</label></div>
@if(!$locked)
<button class="btn btn-outline-primary mr-2" name="submit_kyc" value="0">Save draft</button>
<button class="btn btn-primary" name="submit_kyc" value="1">Submit payout setup</button>
@endif
</div></div>
</form>
@endsection