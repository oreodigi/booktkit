@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">{{ $campaign->name }}</h4><a href="{{ route('admin.mobile_home.index') }}" class="btn btn-sm btn-outline-secondary">Back</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="post" action="{{ route('admin.mobile_home.update',$campaign) }}">@csrf
<div class="row"><div class="col-lg-8"><div class="card"><div class="card-header"><div class="card-title">Homepage Sections</div></div><div class="card-body">
@foreach($campaign->sections as $s)
<div class="border rounded p-3 mb-2"><div class="row align-items-center">
<div class="col-md-2"><label>Order</label><input type="number" class="form-control" name="sections[{{ $s->id }}][position]" value="{{ $s->position }}"></div>
<div class="col-md-7"><strong>{{ ucwords(str_replace('_',' ',$s->type)) }}</strong><input class="form-control mt-1" name="sections[{{ $s->id }}][title]" value="{{ $s->title }}"></div>
<div class="col-md-3"><label class="mt-4"><input type="checkbox" name="sections[{{ $s->id }}][enabled]" {{ $s->enabled?'checked':'' }}> Enabled</label></div>
</div></div>
@endforeach
</div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header"><div class="card-title">Campaign Settings</div></div><div class="card-body">
<div class="form-group"><label>Name</label><input class="form-control" name="name" value="{{ $campaign->name }}" required></div>
<div class="form-group"><label>Template</label><input class="form-control" value="{{ $campaign->template->name }}" disabled></div>
<div class="form-group"><label>Priority</label><input class="form-control" type="number" name="priority" value="{{ $campaign->priority }}"></div>
<div class="form-group"><label>Starts</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ optional($campaign->starts_at)->format('Y-m-d\\TH:i') }}"></div>
<div class="form-group"><label>Ends</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ optional($campaign->ends_at)->format('Y-m-d\\TH:i') }}"></div>
<button class="btn btn-primary btn-block">Save Draft</button>
</div></div></div></div></form>
<div class="card"><div class="card-body d-flex justify-content-between align-items-center"><div><strong>Status: {{ ucfirst($campaign->status) }}</strong><br><small>Publishing creates a version snapshot for rollback/history.</small></div><form method="post" action="{{ route('admin.mobile_home.publish',$campaign) }}">@csrf<button class="btn btn-success">Publish Homepage</button></form></div></div>
@endsection