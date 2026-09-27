@extends('layouts.app')
@section('title', 'Duplicate Contacts')

@push('styles')
<style>
.dup-group{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;margin-bottom:16px;overflow:hidden}
.dup-group-head{padding:12px 16px;background:var(--bg-elevated);border-bottom:1px solid var(--border-subtle);font-size:12.5px;font-weight:700;color:var(--text-200);display:flex;align-items:center;justify-content:space-between}
.dup-table{width:100%;border-collapse:collapse}
.dup-table th{padding:8px 14px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)}
.dup-table td{padding:9px 14px;font-size:12.5px;color:var(--text-100);border-bottom:1px solid var(--border-subtle);vertical-align:middle}
.dup-table tbody tr:last-child td{border-bottom:none}
.dup-empty{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;padding:48px 20px;text-align:center;color:var(--text-300)}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Duplicate Contacts</div>
        <div class="page-sub">Matched by phone or email — pick one record per group to keep, the rest merge into it.</div>
    </div>
    <a href="{{ route('tenant.contacts.index') }}" class="btn btn-secondary">Back to Contacts</a>
</div>

@if ($groups->isEmpty())
<div class="dup-empty">No duplicate contacts found. 🎉</div>
@else
    @foreach ($groups as $gi => $group)
    <form method="POST" action="{{ route('tenant.contacts.duplicates.merge') }}" class="dup-group" onsubmit="return window.confirmMerge(this)">
        @csrf
        <div class="dup-group-head">
            <span>Group {{ $gi + 1 }} — {{ $group->count() }} matching contacts</span>
            <button type="submit" class="btn btn-primary" style="padding:5px 14px;font-size:12px">Merge Selected</button>
        </div>
        <table class="dup-table">
            <thead>
                <tr>
                    <th style="width:70px">Keep</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group as $contact)
                <tr>
                    <td>
                        <input type="radio" name="primary_id" value="{{ $contact->id }}" @checked($loop->first) required>
                    </td>
                    <td>
                        <a href="{{ route('tenant.contacts.show', $contact->id) }}" target="_blank" style="color:var(--text-100);text-decoration:none;font-weight:600">{{ $contact->name }}</a>
                        <input type="checkbox" name="loser_ids[]" value="{{ $contact->id }}" @checked(!$loop->first) style="margin-left:8px" class="loser-cb">
                    </td>
                    <td>{{ $contact->phone }}</td>
                    <td>{{ $contact->email ?? '—' }}</td>
                    <td>{{ $contact->company ?? '—' }}</td>
                    <td>{{ $contact->city ?? '—' }}</td>
                    <td>{{ $contact->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </form>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
window.confirmMerge = function(form){
    const primary = form.querySelector('input[name="primary_id"]:checked');
    const losers  = form.querySelectorAll('input[name="loser_ids[]"]:checked');
    if (!primary) { showToast('Pick a record to keep first.', 'error'); return false; }
    if (!losers.length) { showToast('Select at least one duplicate to merge in.', 'error'); return false; }
    return requireConfirm(form, 'Merge ' + losers.length + ' record(s) into the selected one? This cannot be undone from the UI.', {ok: 'Merge'});
};

document.querySelectorAll('.dup-group').forEach(function(form){
    form.querySelectorAll('input[name="primary_id"]').forEach(function(radio){
        radio.addEventListener('change', function(){
            form.querySelectorAll('.loser-cb').forEach(function(cb){
                if (cb.value === radio.value) cb.checked = false;
            });
        });
    });
});
</script>
@endpush
