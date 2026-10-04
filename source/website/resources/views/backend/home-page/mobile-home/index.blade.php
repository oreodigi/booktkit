@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">{{ __('Mobile Homepage Studio') }}</h4></div>
<div class="row">
 <div class="col-lg-7"><div class="card"><div class="card-header"><div class="card-title">{{ __('Campaigns') }}</div></div><div class="card-body">
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Name</th><th>Template</th><th>Status</th><th>Priority</th><th></th></tr></thead><tbody>
  @foreach($campaigns as $campaign)<tr><td><strong>{{ $campaign->name }}</strong><br><small>{{ optional($campaign->starts_at)->format('d M Y H:i') }} @if($campaign->ends_at) → {{ $campaign->ends_at->format('d M Y H:i') }} @endif</small></td><td>{{ $campaign->template->name }}</td><td><span class="badge badge-{{ $campaign->status==='published'?'success':($campaign->status==='disabled'?'danger':'warning') }}">{{ ucfirst($campaign->status) }}</span></td><td>{{ $campaign->priority }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('admin.mobile_home.edit',$campaign) }}">Manage</a></td></tr>@endforeach
  </tbody></table></div>
 </div></div></div>
 <div class="col-lg-5">
  <div class="card"><div class="card-header"><div class="card-title">{{ __('New Campaign') }}</div></div><div class="card-body"><form method="post" action="{{ route('admin.mobile_home.campaign.store') }}">@csrf
   <div class="form-group"><label>Name</label><input class="form-control" name="name" required placeholder="Diwali 2026"></div>
   <div class="form-group"><label>Template</label><select class="form-control" name="template_id" required>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
   <div class="form-group"><label>Priority</label><input class="form-control" type="number" name="priority" value="10"></div>
   <div class="row"><div class="col-6"><div class="form-group"><label>Starts</label><input class="form-control" type="datetime-local" name="starts_at"></div></div><div class="col-6"><div class="form-group"><label>Ends</label><input class="form-control" type="datetime-local" name="ends_at"></div></div></div>
   <button class="btn btn-primary">Create Draft</button>
  </form></div></div>
  <div class="card"><div class="card-header"><div class="card-title">New Template</div></div><div class="card-body"><form method="post" action="{{ route('admin.mobile_home.template.store') }}">@csrf
   <div class="form-group"><input class="form-control" name="name" placeholder="Festival Gold" required></div><div class="form-group"><select class="form-control" name="template_key"><option value="modern">Modern</option><option value="festival">Festival</option><option value="nightlife">Nightlife</option><option value="sports">Sports</option><option value="minimal">Minimal</option></select></div><button class="btn btn-outline-primary">Create Template</button>
  </form></div></div>
 </div>
</div>
@endsection