@extends('backend.layout')
@section('content')
<div class="page-header"><h4 class="page-title">Route Transfers</h4><a class="btn btn-secondary" href="{{route('admin.organizer_payouts.transfers.export')}}">CSV Export</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Transfer</th><th>Organizer</th><th>Amount</th><th>Status</th><th>Hold</th><th>Error</th><th></th></tr></thead><tbody>
@foreach($transfers as $t)<tr><td>{{$t->gateway_transfer_id?:'Pending'}}</td><td>{{$t->organizer_id}}</td><td>₹{{number_format($t->amount/100,2)}}</td><td>{{$t->status}}</td><td>{{$t->hold_release_at?->format('d M Y H:i')}}</td><td>{{$t->last_error}}</td><td>@if($t->status==='failed')<form method="POST" action="{{route('admin.organizer_payouts.transfers.retry',$t->id)}}">@csrf<button class="btn btn-sm btn-warning">Retry</button></form>@endif</td></tr>@endforeach
</tbody></table>{{$transfers->links()}}</div></div>
@endsection