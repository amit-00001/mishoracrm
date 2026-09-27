@extends('layouts.app')
@section('title', 'Coupon Management')

@push('styles')
<style>
.table-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:12px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); }
.data-table td { padding:13px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg-hover); }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green  { background:var(--green-dim); color:var(--green); }
.badge-red    { background:var(--red-dim); color:var(--red); }
.badge-blue   { background:var(--accent-dim); color:var(--accent); }
.badge-yellow { background:var(--amber-dim); color:var(--amber); }
.badge-gray   { background:var(--bg-input); color:var(--text-400); }
.code-chip { font-family:var(--mono); font-size:13px; font-weight:700; background:var(--bg-input); padding:3px 8px; border-radius:6px; color:var(--text-100); letter-spacing:.05em; }
.action-btns { display:flex; gap:6px; }
.empty-state { text-align:center; padding:56px 16px; color:var(--text-400); font-size:14px; }
</style>
@endpush

@section('content')

    <div class="page-head">
        <div class="page-title">Coupon Management</div>
        <a href="{{ route('superadmin.coupons.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Coupon
        </a>
    </div>

    @if(session('success'))
    <div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
        ✓ {{ session('success') }}
    </div>
    @endif

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Discount</th>
                    <th>Applicable To</th>
                    <th>Uses</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    <td data-label="Code"><span class="code-chip">{{ $coupon->code }}</span></td>
                    <td data-label="Name">
                        <div style="font-weight:600;color:var(--text-100)">{{ $coupon->name }}</div>
                        @if($coupon->description)
                            <div style="font-size:12px;color:var(--text-400)">{{ $coupon->description }}</div>
                        @endif
                    </td>
                    <td data-label="Discount">
                        <span class="badge badge-blue">{{ $coupon->discount_label }}</span>
                        @if($coupon->max_discount)
                            <div style="font-size:11px;color:var(--text-400);margin-top:2px">Max ₹{{ number_format($coupon->max_discount) }}</div>
                        @endif
                    </td>
                    <td data-label="Applicable To">
                        @if($coupon->applicable_to === 'all')
                            <span class="badge badge-green">All Users</span>
                        @else
                            <span class="badge badge-yellow">{{ $coupon->users->count() }} Specific User(s)</span>
                            @foreach($coupon->users->take(3) as $u)
                                <div style="font-size:12px;color:var(--text-100);margin-top:3px;font-weight:600">
                                    {{ $u->name }}
                                    @if($u->tenant)<span style="font-weight:400;color:var(--text-400)"> · {{ $u->tenant->name }}</span>@endif
                                </div>
                            @endforeach
                            @if($coupon->users->count() > 3)
                                <div style="font-size:11px;color:var(--text-400)">+{{ $coupon->users->count() - 3 }} more</div>
                            @endif
                        @endif
                    </td>
                    <td data-label="Uses">
                        {{ $coupon->used_count }}
                        @if($coupon->max_uses)
                            <span style="color:var(--text-400)"> / {{ $coupon->max_uses }}</span>
                        @else
                            <span style="color:var(--text-400)"> / ∞</span>
                        @endif
                    </td>
                    <td data-label="Expires">
                        @if($coupon->expires_at)
                            <span style="color: {{ $coupon->expires_at->isPast() ? 'var(--red)' : 'var(--text-200)' }}">
                                {{ $coupon->expires_at->format('d M Y') }}
                            </span>
                        @else
                            <span style="color:var(--text-400)">Never</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $coupon->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('superadmin.coupons.edit', $coupon) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('superadmin.coupons.toggle', $coupon) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent)">
                                    {{ $coupon->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form action="{{ route('superadmin.coupons.destroy', $coupon) }}" method="POST" style="display:inline"
                                  data-confirm="Delete coupon {{ $coupon->code }}?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            No coupons yet. <a href="{{ route('superadmin.coupons.create') }}" style="color:var(--accent)">Create your first coupon →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
    <div style="margin-top:16px">{{ $coupons->links() }}</div>
    @endif

@endsection
