@if(session('staff_impersonated_by_admin'))
<div style="background:#b02a37;color:#fff;padding:8px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
  <span><strong>Admin view:</strong> you are viewing as {{ optional(auth('staff')->user())->name }} (read-only).</span>
  <form method="post" action="{{ route('staff.impersonation.end') }}" style="margin:0">@csrf<button class="btn btn-sm btn-light">Return to admin</button></form>
</div>
@endif
