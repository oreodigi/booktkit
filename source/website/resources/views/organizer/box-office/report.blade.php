@extends('organizer.layout')
@section('content')
<div class="page-header"><h4 class="page-title">Box Office Reports</h4></div>
<div class="row">
<div class="col-md-3"><div class="card"><div class="card-body"><b>Gross</b><p>{{ number_format($summary['gross']/100,2) }}</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><b>Organizer</b><p>{{ number_format($summary['organizer_amount']/100,2) }}</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><b>Platform fee</b><p>{{ number_format($summary['platform_fee']/100,2) }}</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><b>Voids / Reprints</b><p>{{ $summary['voids'] }} / {{ $reprints }}</p></div></div></div>
</div>
<div class="card"><div class="card-body">
<p>Online bookings: {{ $online }} | Box office bookings: {{ $box }} | Checked-in tickets: {{ $admissions }}</p>
<h5>Payment split</h5><table class="table"><tr><th>Method</th><th>Sales</th><th>Total</th></tr>@foreach($payments as $method=>$p)<tr><td>{{ strtoupper($method) }}</td><td>{{ $p['count'] }}</td><td>{{ number_format($p['total']/100,2) }}</td></tr>@endforeach</table>
<h5>Shift variance</h5><table class="table"><tr><th>Shift</th><th>Status</th><th>Expected</th><th>Declared</th><th>Variance</th></tr>@foreach($shifts as $s)<tr><td>#{{ $s->id }}</td><td>{{ $s->status }}</td><td>{{ is_null($s->expected_cash)?'—':number_format($s->expected_cash/100,2) }}</td><td>{{ is_null($s->declared_closing_cash)?'—':number_format($s->declared_closing_cash/100,2) }}</td><td>{{ is_null($s->variance)?'—':number_format($s->variance/100,2) }}</td></tr>@endforeach</table>
</div></div>
@endsection