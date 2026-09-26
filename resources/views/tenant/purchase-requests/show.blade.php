@extends('layouts.app')
@section('title', 'Purchase Request — ' . $purchaseRequest->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.ps { font-family: 'DM Sans', var(--font), sans-serif; }
.ps-layout { display:grid; grid-template-columns:minmax(0,1fr) 290px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .ps-layout { grid-template-columns:1fr; } }
.ps-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.ps-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); }
.ps-card-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }
.ps-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.ps-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.ps-hero { padding:22px; }
.ps-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; margin-bottom:3px; }
.ps-status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.ps-items-table { width:100%; border-collapse:collapse; }
.ps-items-table thead tr { background:var(--bg-elevated); }
.ps-items-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.ps-items-table td { padding:13px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.ps-items-table tr:last-child td { border-bottom:none; }
.dl-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.dl-row:last-child { border-bottom:none; }
.dl-key { font-size:12px; color:var(--text-300); }
.dl-val { font-size:12.5px; font-weight:500; color:var(--text-100); text-align:right; }
.qs-action-btn { display:flex; align-items:center; gap:9px; padding:10px 13px; border-radius:9px; border:1px solid var(--border-default); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.qs-action-btn:hover { background:var(--bg-surface); border-color:var(--border-strong); }
.qs-action-btn + .qs-action-btn { margin-top:7px; }
.qs-act-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'pending'   => ['bg' => 'var(--amber-dim)', 'color' => 'var(--amber)', 'text' => 'var(--amber)', 'icon' => 'ti-clock'],
        'approved'  => ['bg' => 'var(--green-dim)', 'color' => 'var(--green)', 'text' => 'var(--green)', 'icon' => 'ti-circle-check'],
        'rejected'  => ['bg' => 'var(--red-dim)', 'color' => 'var(--red)', 'text' => 'var(--red)', 'icon' => 'ti-circle-x'],
        'converted' => ['bg' => 'var(--accent-dim)', 'color' => 'var(--accent)', 'text' => 'var(--accent)', 'icon' => 'ti-arrow-right'],
    ];
    $st = $statusMeta[$purchaseRequest->status] ?? $statusMeta['pending'];
    $items = $purchaseRequest->items ?? [];
@endphp

<div class="ps">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.purchase-requests.index') }}" style="color:var(--text-300);text-decoration:none">Purchase Requests</a>
                <span style="opacity:.4">›</span>
                <span>{{ $purchaseRequest->number }}</span>
            </div>
            <div class="page-title">Purchase Request Detail</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @can('modify', $purchaseRequest)
            <a href="{{ route('tenant.purchase-requests.edit',$purchaseRequest->id) }}" class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    @foreach(['success','error'] as $type)
    @if(session($type))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:{{ $type==='success'?'var(--green-dim)':'var(--red-dim)' }};border:1px solid {{ $type==='success'?'var(--green)':'var(--red)' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $type==='success'?'var(--green)':'var(--red)' }};font-weight:500">
        {{ session($type) }}
    </div>
    @endif
    @endforeach

    <div class="ps-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="ps-card">
                <div class="ps-hero">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <div>
                            <div class="ps-number">{{ $purchaseRequest->number }}</div>
                            <div style="font-size:13px;color:var(--text-300)">Requested by {{ $purchaseRequest->requestedBy?->name ?? '—' }}</div>
                        </div>
                        <span class="ps-status-badge" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};border:1px solid {{ $st['color'] }}40">
                            <i class="ti {{ $st['icon'] }}" style="font-size:14px"></i> {{ ucfirst($purchaseRequest->status) }}
                        </span>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:18px">
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Date</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100);font-family:'DM Mono',monospace">{{ $purchaseRequest->date?->format('M d, Y') }}</div>
                        </div>
                        @if($purchaseRequest->department)
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Department</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100)">{{ $purchaseRequest->department->name }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title"><i class="ti ti-list-details" style="font-size:13px;margin-right:5px"></i> Items ({{ count($items) }})</div>
                </div>
                <div style="overflow-x:auto">
                    <table class="ps-items-table">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:30%">Item</th>
                                <th style="width:30%">Description</th>
                                <th style="width:10%;text-align:right">Qty</th>
                                <th style="width:25%">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $idx => $item)
                            <tr>
                                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-400)">{{ $idx+1 }}</td>
                                <td style="font-weight:500">{{ $item['name'] ?? '—' }}</td>
                                <td style="color:var(--text-300);font-size:12.5px">{{ $item['description'] ?? '—' }}</td>
                                <td style="text-align:right;font-family:'DM Mono',monospace">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                                <td style="color:var(--text-300);font-size:12.5px">{{ $item['reason'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($purchaseRequest->reason)
                <div style="padding:16px 20px;border-top:1px solid var(--border-subtle)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Reason</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6">{{ $purchaseRequest->reason }}</div>
                </div>
                @endif
            </div>

            @if($purchaseRequest->status === 'rejected' && $purchaseRequest->rejection_reason)
            <div class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title" style="color:var(--red)"><i class="ti ti-circle-x" style="font-size:13px;margin-right:5px"></i> Rejection Reason</div>
                </div>
                <div style="padding:16px 20px;font-size:13px;color:var(--text-200)">{{ $purchaseRequest->rejection_reason }}</div>
            </div>
            @endif

            @if($purchaseRequest->purchaseOrder)
            <div class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title"><i class="ti ti-file-invoice" style="font-size:13px;margin-right:5px"></i> Linked Purchase Order</div>
                </div>
                <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $purchaseRequest->purchaseOrder->number }}</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px">Status: {{ ucfirst(str_replace('_',' ',$purchaseRequest->purchaseOrder->status)) }}</div>
                    </div>
                    <a href="{{ route('tenant.purchase-orders.show', $purchaseRequest->purchaseOrder->id) }}" class="btn btn-secondary" style="font-size:12px;padding:6px 12px">View PO</a>
                </div>
            </div>
            @endif

        </div>

        <div style="display:flex;flex-direction:column;gap:14px">

            @can('approve', $purchaseRequest)
            <div class="ps-sc">
                <div class="ps-sc-title">Approval</div>
                <form method="POST" action="{{ route('tenant.purchase-requests.approve',$purchaseRequest->id) }}"
                      data-submit-once data-confirm-danger="false" data-confirm-title="Approve request?" data-confirm-ok="Approve"
                      data-confirm="Approve this request? A draft Purchase Order will be created automatically.">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="background:var(--green-dim);border-color:var(--green);color:var(--green)">
                        <div class="qs-act-icon" style="background:var(--green-dim)"><i class="ti ti-circle-check" style="font-size:15px;color:var(--green)"></i></div>
                        Approve Request
                    </button>
                </form>
                <form method="POST" action="{{ route('tenant.purchase-requests.reject',$purchaseRequest->id) }}" style="margin-top:10px" id="rejectForm"
                      data-submit-once data-confirm-title="Reject request?" data-confirm-ok="Reject" data-confirm="Reject this request?">
                    @csrf
                    <textarea name="rejection_reason" class="pf-input" rows="2" placeholder="Rejection reason (optional)"
                              style="width:100%;padding:8px 10px;border:1.5px solid var(--border-default);border-radius:7px;background:var(--bg-input);color:var(--text-100);font-family:'DM Sans',var(--font),sans-serif;font-size:12.5px;margin-bottom:8px"></textarea>
                    <button type="submit" class="qs-action-btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                        <div class="qs-act-icon" style="background:var(--red-dim)"><i class="ti ti-circle-x" style="font-size:15px;color:var(--red)"></i></div>
                        Reject Request
                    </button>
                </form>
            </div>
            @endcan

            <div class="ps-sc">
                <div class="ps-sc-title">Details</div>
                <div>
                    <div class="dl-row"><span class="dl-key">Number</span><span class="dl-val" style="font-family:'DM Mono',monospace">{{ $purchaseRequest->number }}</span></div>
                    <div class="dl-row"><span class="dl-key">Items</span><span class="dl-val">{{ count($items) }}</span></div>
                    <div class="dl-row"><span class="dl-key">Requested By</span><span class="dl-val">{{ $purchaseRequest->requestedBy?->name ?? '—' }}</span></div>
                    @if($purchaseRequest->approvedBy)
                    <div class="dl-row"><span class="dl-key">Approved By</span><span class="dl-val">{{ $purchaseRequest->approvedBy->name }}</span></div>
                    <div class="dl-row"><span class="dl-key">Approved At</span><span class="dl-val">{{ $purchaseRequest->approved_at?->format('d M Y') }}</span></div>
                    @endif
                    <div class="dl-row"><span class="dl-key">Created</span><span class="dl-val">{{ $purchaseRequest->created_at->format('M d, Y') }}</span></div>
                </div>
            </div>

            @can('delete', $purchaseRequest)
            <div class="ps-sc" style="border-color:var(--red)">
                <div class="ps-sc-title" style="color:var(--red)">Danger Zone</div>
                <form method="POST" action="{{ route('tenant.purchase-requests.destroy',$purchaseRequest->id) }}"
                      data-submit-once data-confirm-title="Delete request?" data-confirm-ok="Delete"
                      data-confirm="Delete purchase request {{ $purchaseRequest->number }}?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                        <i class="ti ti-trash" style="font-size:14px"></i> Delete Request
                    </button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection
