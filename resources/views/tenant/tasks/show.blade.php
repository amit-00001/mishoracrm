@extends('layouts.app')

@section('title', $task->title)

@php
    $taskConfig = config('task_fields');

    $statuses = $taskConfig['stages'];

    $statusData = $statuses[$task->status] ?? [
        'label'      => ucfirst($task->status),
        'color'      => '#3B82F6',
        'bg'         => '#EFF6FF',
        'text_color' => '#1E40AF',
    ];
@endphp

@push('styles')
<style>
.df-show{
    font-family:'DM Sans',sans-serif;
}

/* ─────────────────────────────
   Header
───────────────────────────── */
.ts-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}

@media(max-width:700px){
    .ts-top{
        flex-direction:column;
    }
}

/* ─────────────────────────────
   Layout
───────────────────────────── */
.ts-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 340px;
    gap:18px;
}

@media(max-width:1050px){
    .ts-layout{
        grid-template-columns:1fr;
    }
}

/* ─────────────────────────────
   Card
───────────────────────────── */
.ts-card{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:18px;
    overflow:hidden;
}

.ts-head{
    padding:18px 22px;
    border-bottom:1px solid var(--border-subtle);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}

.ts-title{
    font-size:15px;
    font-weight:700;
    color:var(--text-100);
}

.ts-sub{
    margin-top:3px;
    font-size:12px;
    color:var(--text-400);
}

.ts-body{
    padding:22px;
}

/* ─────────────────────────────
   Hero
───────────────────────────── */
.task-hero{
    padding:28px;
    background:
        radial-gradient(circle at top right,
        rgba(59,130,246,.15),
        transparent 30%),
        var(--bg-surface);

    border-bottom:1px solid var(--border-subtle);
}

.task-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.task-dot{
    width:8px;
    height:8px;
    border-radius:50%;
}

.task-title-main{
    margin-top:18px;
    font-size:34px;
    line-height:1.3;
    font-weight:800;
    color:var(--text-100);
    letter-spacing:-1px;
}

.task-desc{
    margin-top:16px;
    max-width:900px;
    font-size:14px;
    line-height:1.9;
    color:var(--text-300);
    white-space:pre-wrap;
}

/* ─────────────────────────────
   Stats
───────────────────────────── */
.ts-stats{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
}

@media(max-width:900px){
    .ts-stats{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:500px){
    .ts-stats{
        grid-template-columns:1fr;
    }
}

.stat-box{
    padding:16px;
    border-radius:16px;
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
}

.stat-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--text-400);
    font-weight:700;
}

.stat-value{
    margin-top:10px;
    font-size:15px;
    font-weight:700;
    color:var(--text-100);
}

.stat-icon{
    width:40px;
    height:40px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--accent-dim);
    color:var(--accent);
    margin-bottom:14px;
}

/* ─────────────────────────────
   Details
───────────────────────────── */
.details-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

@media(max-width:700px){
    .details-grid{
        grid-template-columns:1fr;
    }
}

.detail-card{
    padding:18px;
    border-radius:16px;
    border:1px solid var(--border-subtle);
    background:var(--bg-elevated);
}

.detail-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--text-400);
    font-weight:700;
}

.detail-value{
    margin-top:8px;
    font-size:14px;
    font-weight:600;
    color:var(--text-100);
}

/* ─────────────────────────────
   Sidebar
───────────────────────────── */
.side-stack{
    display:flex;
    flex-direction:column;
    gap:18px;
}

/* ─────────────────────────────
   Activity
───────────────────────────── */
.timeline{
    position:relative;
    padding-left:20px;
}

.timeline:before{
    content:'';
    position:absolute;
    left:4px;
    top:0;
    bottom:0;
    width:2px;
    background:var(--border-default);
}

.tl-item{
    position:relative;
    padding-bottom:22px;
}

.tl-dot{
    position:absolute;
    left:-20px;
    top:6px;
    width:10px;
    height:10px;
    border-radius:50%;
    background:var(--accent);
}

.tl-title{
    font-size:13px;
    font-weight:700;
    color:var(--text-100);
}

.tl-date{
    margin-top:4px;
    font-size:12px;
    color:var(--text-400);
}

/* ─────────────────────────────
   Priority
───────────────────────────── */
.priority{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.priority.low{
    background:#1d9e7515;
    color:var(--green);
}

.priority.medium{
    background:#f59e0b15;
    color:#F59E0B;
}

.priority.high{
    background:#ef444415;
    color:#EF4444;
}

/* ─────────────────────────────
   Buttons
───────────────────────────── */
.top-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

/* ─────────────────────────────
   Checklist / Comments / Attachments / Watchers
───────────────────────────── */
.mini-input{
    flex:1;
    padding:9px 12px;
    border-radius:8px;
    border:1.5px solid var(--border-default);
    background:var(--bg-input);
    color:var(--text-100);
    font-size:13px;
    outline:none;
}

.mini-input:focus{
    border-color:var(--accent);
}

.mini-form{
    display:flex;
    gap:8px;
    margin-bottom:14px;
}

.chk-progress-track{
    height:6px;
    border-radius:99px;
    background:var(--bg-elevated);
    overflow:hidden;
    margin-bottom:14px;
}

.chk-progress-fill{
    height:100%;
    background:var(--accent);
    border-radius:99px;
}

.chk-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 0;
    border-bottom:1px solid var(--border-subtle);
}

.chk-item:last-child{
    border-bottom:none;
}

.chk-item input[type=checkbox]{
    width:16px;
    height:16px;
    cursor:pointer;
    accent-color:var(--accent);
    flex-shrink:0;
}

.chk-title{
    flex:1;
    font-size:13px;
    color:var(--text-100);
}

.chk-title.done{
    text-decoration:line-through;
    color:var(--text-400);
}

.chk-del{
    background:none;
    border:none;
    cursor:pointer;
    color:var(--text-400);
    font-size:14px;
    padding:2px;
}

.chk-del:hover{
    color:var(--red);
}

.cm-item{
    padding:12px 0;
    border-bottom:1px solid var(--border-subtle);
}

.cm-item:last-child{
    border-bottom:none;
}

.cm-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
}

.cm-author{
    font-size:12.5px;
    font-weight:700;
    color:var(--text-100);
}

.cm-time{
    font-size:11px;
    color:var(--text-400);
}

.cm-body{
    font-size:13px;
    color:var(--text-300);
    line-height:1.6;
    white-space:pre-wrap;
}

.att-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 0;
    border-bottom:1px solid var(--border-subtle);
}

.att-item:last-child{
    border-bottom:none;
}

.att-icon{
    width:32px;
    height:32px;
    border-radius:8px;
    background:var(--bg-elevated);
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--accent);
    flex-shrink:0;
}

.att-name{
    font-size:12.5px;
    font-weight:600;
    color:var(--text-100);
    text-decoration:none;
    display:block;
}

.att-meta{
    font-size:11px;
    color:var(--text-400);
}

.empty-hint{
    font-size:12.5px;
    color:var(--text-400);
    text-align:center;
    padding:14px 0;
}

.watcher-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px 5px 5px;
    border-radius:999px;
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
    font-size:12px;
    margin:3px 4px 3px 0;
}

.watcher-avatar{
    width:20px;
    height:20px;
    border-radius:50%;
    background:var(--accent-dim);
    color:var(--accent);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:9px;
    font-weight:700;
}

.watcher-remove{
    background:none;
    border:none;
    cursor:pointer;
    color:var(--text-400);
    font-size:12px;
    padding:0;
}
</style>
@endpush

@section('content')

<div class="df-show">

    {{-- TOP --}}
    <div class="ts-top">

        <div>

            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">

                <a href="{{ route('tenant.tasks.index') }}"
                   style="text-decoration:none;color:inherit">
                    Tasks
                </a>

                › {{ $task->title }}

            </div>

            <div class="page-title">
                Task Overview
            </div>

        </div>

        <div class="top-actions">

            <a href="{{ route('tenant.tasks.edit', $task->id) }}"
               class="btn btn-primary">

                <i class="ti ti-edit"></i>
                Edit Task

            </a>

            <a href="{{ route('tenant.tasks.index') }}"
               class="btn btn-secondary">

                <i class="ti ti-arrow-left"></i>
                Back

            </a>

        </div>

    </div>

    <div class="ts-layout">

        {{-- MAIN --}}
        <div>

            <div class="ts-card">

                {{-- HERO --}}
                <div class="task-hero">

                    <div class="task-badge"
                         style="
                            background:{{ $statusData['bg'] }};
                            color:{{ $statusData['text_color'] }};
                         ">

                        <span class="task-dot"
                              style="background:{{ $statusData['color'] }}"></span>

                        {{ $statusData['label'] }}

                    </div>

                    <div class="task-title-main">
                        {{ $task->title }}
                    </div>

                    @if($task->description)
                    <div class="task-desc">
                        {{ $task->description }}
                    </div>
                    @endif

                    @if(!empty($task->tags))
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:14px">
                        @foreach($task->tags as $tag)
                        <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:600;background:var(--accent-dim);color:var(--accent)">
                            {{ $tag }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    @if($task->isRecurring())
                    @php
                        $recurrenceUnits = ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'];
                        $unit = $recurrenceUnits[$task->recurrence_type] ?? $task->recurrence_type;
                    @endphp
                    <div style="display:flex;align-items:center;gap:6px;margin-top:14px;font-size:12.5px;color:var(--text-300)">
                        <i class="ti ti-repeat"></i>
                        Repeats every {{ $task->recurrence_interval > 1 ? $task->recurrence_interval . ' ' : '' }}{{ \Illuminate\Support\Str::plural($unit, $task->recurrence_interval) }}
                        @if($task->recurrence_end_date)
                            until {{ $task->recurrence_end_date->format('d M Y') }}
                        @endif
                    </div>
                    @endif

                    @if($task->recurrence_parent_id)
                    <div style="margin-top:8px;font-size:12.5px">
                        <a href="{{ route('tenant.tasks.show', $task->recurrence_parent_id) }}" style="color:var(--accent);text-decoration:none">
                            <i class="ti ti-repeat"></i> Part of a recurring series — view original
                        </a>
                    </div>
                    @endif

                </div>

                {{-- BODY --}}
                <div class="ts-body">

                    {{-- STATS --}}
                    <div class="ts-stats">

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-user"></i>
                            </div>

                            <div class="stat-label">
                                Assigned To
                            </div>

                            <div class="stat-value">
                                {{ $task->assignedTo?->name ?? 'Unassigned' }}
                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-flag"></i>
                            </div>

                            <div class="stat-label">
                                Priority
                            </div>

                            <div class="stat-value">

                                <span class="priority {{ $task->priority }}">
                                    {{ ucfirst($task->priority) }}
                                </span>

                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-calendar"></i>
                            </div>

                            <div class="stat-label">
                                Due Date
                            </div>

                            <div class="stat-value">
                                {{ $task->due_at ? $task->due_at->format('d M Y') : 'Not Set' }}
                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-clock"></i>
                            </div>

                            <div class="stat-label">
                                Created
                            </div>

                            <div class="stat-value">
                                {{ $task->created_at->diffForHumans() }}
                            </div>

                        </div>

                        @if($task->estimated_hours || $task->actual_hours)
                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-hourglass"></i>
                            </div>

                            <div class="stat-label">
                                Time Tracked
                            </div>

                            <div class="stat-value">
                                @if($task->estimated_hours)
                                    Est. {{ rtrim(rtrim(number_format($task->estimated_hours, 2), '0'), '.') }}h
                                @endif
                                @if($task->actual_hours)
                                    @if($task->estimated_hours) · @endif
                                    Actual {{ rtrim(rtrim(number_format($task->actual_hours, 2), '0'), '.') }}h
                                    @if($task->estimated_hours)
                                        @php $variance = $task->actual_hours - $task->estimated_hours; @endphp
                                        <span style="color:{{ $variance > 0 ? 'var(--red)' : 'var(--green)' }};font-size:11px;font-weight:700">
                                            ({{ $variance > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($variance, 2), '0'), '.') }}h)
                                        </span>
                                    @endif
                                @endif
                            </div>

                        </div>
                        @endif

                    </div>

                    {{-- DETAILS --}}
                    <div class="mt-4">

                        <div class="ts-title mb-3">
                            Task Details
                        </div>

                        <div class="details-grid">

                            <div class="detail-card">

                                <div class="detail-label">
                                    Created By
                                </div>

                                <div class="detail-value">
                                    {{ $task->creator?->name ?? 'System' }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Last Updated
                                </div>

                                <div class="detail-value">
                                    {{ $task->updated_at->format('d M Y h:i A') }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Task Status
                                </div>

                                <div class="detail-value">
                                    {{ $statusData['label'] }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Task ID
                                </div>

                                <div class="detail-value">
                                    #{{ $task->id }}
                                </div>

                            </div>

                        </div>

                    </div>

                    @include('components.custom-fields-display')

                </div>

            </div>

            @if(auth()->user()->tenant?->hasModuleEnabled('service'))
            {{-- TIME TRACKING --}}
            @php
                $runningEntry = $task->timeEntries->firstWhere(fn($e) => $e->isRunning() && $e->user_id === auth()->id());
                $taskContactId = $task->taskable_type === \App\Models\Contact::class ? $task->taskable_id : null;
            @endphp
            <div class="ts-card" style="margin-top:18px">
                <div class="ts-head">
                    <div>
                        <div class="ts-title">Time Tracking</div>
                        <div class="ts-sub">{{ $task->actual_hours ? number_format($task->actual_hours, 2) . ' hrs logged' : 'No time logged yet' }}</div>
                    </div>
                </div>
                <div class="ts-body">
                    @if($runningEntry)
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px">
                        <div style="width:10px;height:10px;border-radius:50%;background:var(--red);animation:tt-pulse 1.5s infinite"></div>
                        <div class="mono" id="taskLiveTimer" style="font-size:18px;font-weight:700;color:var(--accent)" data-started="{{ $runningEntry->started_at->toIso8601String() }}">00:00:00</div>
                        <form method="POST" action="{{ route('tenant.time-entries.stop', $runningEntry->id) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm" type="submit">Stop Timer</button>
                        </form>
                    </div>
                    <style>@keyframes tt-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }</style>
                    <script>
                    (function(){
                        const el = document.getElementById('taskLiveTimer');
                        if(!el) return;
                        const started = new Date(el.dataset.started).getTime();
                        setInterval(() => {
                            const diff = Math.floor((Date.now() - started) / 1000);
                            const h = String(Math.floor(diff/3600)).padStart(2,'0');
                            const m = String(Math.floor((diff%3600)/60)).padStart(2,'0');
                            const s = String(diff%60).padStart(2,'0');
                            el.textContent = `${h}:${m}:${s}`;
                        }, 1000);
                    })();
                    </script>
                    @else
                    <form method="POST" action="{{ route('tenant.time-entries.start') }}" style="margin-bottom:14px">
                        @csrf
                        <input type="hidden" name="task_id" value="{{ $task->id }}"/>
                        @if($taskContactId)<input type="hidden" name="contact_id" value="{{ $taskContactId }}"/>@endif
                        <button class="btn btn-primary btn-sm" type="submit">▶ Start Timer</button>
                    </form>
                    @endif

                    <details style="margin-bottom:14px">
                        <summary style="cursor:pointer;font-size:12.5px;color:var(--text-300)">+ Log time manually</summary>
                        <form method="POST" action="{{ route('tenant.time-entries.store') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:flex-end">
                            @csrf
                            <input type="hidden" name="task_id" value="{{ $task->id }}"/>
                            @if($taskContactId)<input type="hidden" name="contact_id" value="{{ $taskContactId }}"/>@endif
                            <div>
                                <label style="font-size:11px;color:var(--text-400);display:block;margin-bottom:4px">Date</label>
                                <input type="date" name="date" value="{{ now()->toDateString() }}" required style="padding:7px 10px;border:1.5px solid var(--border-default);border-radius:6px;background:var(--bg-input);color:var(--text-100);font-size:12.5px"/>
                            </div>
                            <div>
                                <label style="font-size:11px;color:var(--text-400);display:block;margin-bottom:4px">Hours</label>
                                <input type="number" name="hours" step="0.25" min="0.25" max="24" required style="width:80px;padding:7px 10px;border:1.5px solid var(--border-default);border-radius:6px;background:var(--bg-input);color:var(--text-100);font-size:12.5px"/>
                            </div>
                            <button class="btn btn-secondary btn-sm" type="submit">Log Time</button>
                        </form>
                    </details>

                    @if($task->timeEntries->isNotEmpty())
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--text-400);text-transform:uppercase;border-bottom:1px solid var(--border-subtle)">Staff</th>
                                <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--text-400);text-transform:uppercase;border-bottom:1px solid var(--border-subtle)">Date</th>
                                <th style="text-align:left;padding:6px 8px;font-size:11px;color:var(--text-400);text-transform:uppercase;border-bottom:1px solid var(--border-subtle)">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($task->timeEntries as $entry)
                            <tr>
                                <td style="padding:6px 8px;font-size:12.5px;color:var(--text-200);border-bottom:1px solid var(--border-subtle)">{{ $entry->user?->name ?? '—' }}</td>
                                <td style="padding:6px 8px;font-size:12.5px;color:var(--text-200);border-bottom:1px solid var(--border-subtle)" class="mono">{{ $entry->started_at->format('d M Y') }}</td>
                                <td style="padding:6px 8px;font-size:12.5px;color:var(--text-200);border-bottom:1px solid var(--border-subtle)" class="mono">{{ $entry->isRunning() ? 'Running…' : $entry->durationHours() . 'h' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @endif

            {{-- CHECKLIST --}}
            <div class="ts-card" style="margin-top:18px">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Checklist</div>
                        <div class="ts-sub">
                            {{ $task->checklist_progress['done'] }} of {{ $task->checklist_progress['total'] }} done
                        </div>
                    </div>
                </div>

                <div class="ts-body">

                    @if($task->checklistItems->count())
                    <div class="chk-progress-track">
                        <div class="chk-progress-fill"
                             style="width:{{ $task->checklist_progress['total'] ? round($task->checklist_progress['done'] / $task->checklist_progress['total'] * 100) : 0 }}%"></div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.tasks.checklist.store', $task->id) }}" class="mini-form">
                        @csrf
                        <input type="text" name="title" class="mini-input" placeholder="Add a checklist item..." required maxlength="255">
                        <button type="submit" class="btn btn-secondary"><i class="ti ti-plus"></i></button>
                    </form>

                    @forelse($task->checklistItems as $item)
                    <div class="chk-item">
                        <form method="POST" action="{{ route('tenant.tasks.checklist.toggle', [$task->id, $item->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="checkbox" onchange="this.form.submit()" {{ $item->is_done ? 'checked' : '' }}>
                        </form>
                        <span class="chk-title {{ $item->is_done ? 'done' : '' }}">{{ $item->title }}</span>
                        <form method="POST" action="{{ route('tenant.tasks.checklist.destroy', [$task->id, $item->id]) }}" data-confirm="Remove this item?" data-confirm-ok="Remove">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="chk-del"><i class="ti ti-x"></i></button>
                        </form>
                    </div>
                    @empty
                    <div class="empty-hint">No checklist items yet.</div>
                    @endforelse

                </div>

            </div>

            {{-- DEPENDENCIES --}}
            <div class="ts-card" style="margin-top:18px">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Dependencies</div>
                        <div class="ts-sub">Tasks that must finish first</div>
                    </div>
                </div>

                <div class="ts-body">

                    @if($task->hasIncompleteDependencies())
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.3);border-radius:8px;margin-bottom:14px;font-size:12.5px;color:var(--red)">
                        <i class="ti ti-lock"></i>
                        Blocked — this task can't be marked completed until its dependencies are done.
                    </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.tasks.dependencies.store', $task->id) }}" class="mini-form">
                        @csrf
                        <select name="depends_on_task_id" class="mini-input" required>
                            <option value="">— Select task this is blocked by —</option>
                            @foreach($otherTasks as $ot)
                            <option value="{{ $ot->id }}">{{ $ot->title }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary"><i class="ti ti-plus"></i></button>
                    </form>

                    <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">
                        Blocked by ({{ $task->dependencies->count() }})
                    </div>

                    @forelse($task->dependencies as $dep)
                    @php $depStatus = config('task_fields.stages')[$dep->status] ?? null; @endphp
                    <div class="chk-item">
                        <a href="{{ route('tenant.tasks.show', $dep->id) }}" class="chk-title" style="text-decoration:none">{{ $dep->title }}</a>
                        @if($depStatus)
                        <span class="tt-badge" style="background:{{ $depStatus['bg'] }};color:{{ $depStatus['text_color'] }};font-size:11px;padding:2px 8px;border-radius:99px">{{ $depStatus['label'] }}</span>
                        @endif
                        <form method="POST" action="{{ route('tenant.tasks.dependencies.destroy', [$task->id, $dep->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="chk-del"><i class="ti ti-x"></i></button>
                        </form>
                    </div>
                    @empty
                    <div class="empty-hint">No dependencies — this task can start anytime.</div>
                    @endforelse

                    @if($task->dependents->isNotEmpty())
                    <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;margin:16px 0 6px">
                        Blocks ({{ $task->dependents->count() }})
                    </div>
                    @foreach($task->dependents as $dep)
                    <div class="chk-item">
                        <a href="{{ route('tenant.tasks.show', $dep->id) }}" class="chk-title" style="text-decoration:none">{{ $dep->title }}</a>
                    </div>
                    @endforeach
                    @endif

                </div>

            </div>

            {{-- ATTACHMENTS --}}
            <div class="ts-card" style="margin-top:18px">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Attachments</div>
                        <div class="ts-sub">{{ $task->attachments->count() }} file(s)</div>
                    </div>
                </div>

                <div class="ts-body">

                    <form method="POST" action="{{ route('tenant.tasks.attachments.store', $task->id) }}" enctype="multipart/form-data" class="mini-form">
                        @csrf
                        <input type="file" name="attachments[]" class="mini-input" multiple>
                        <button type="submit" class="btn btn-secondary"><i class="ti ti-upload"></i></button>
                    </form>

                    @forelse($task->attachments as $file)
                    <div class="att-item">
                        <div class="att-icon"><i class="ti ti-{{ $file->isImage() ? 'photo' : ($file->isPdf() ? 'file-type-pdf' : 'file') }}"></i></div>
                        <div style="flex:1;min-width:0">
                            <a href="{{ $file->url }}" target="_blank" class="att-name">{{ $file->original_name }}</a>
                            <div class="att-meta">{{ $file->file_size_human }} · {{ $file->uploadedBy?->name ?? 'Unknown' }}</div>
                        </div>
                        <form method="POST" action="{{ route('tenant.tasks.attachments.destroy', [$task->id, $file->id]) }}" data-confirm="Delete this attachment?" data-confirm-ok="Delete">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="chk-del"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>
                    @empty
                    <div class="empty-hint">No attachments yet.</div>
                    @endforelse

                </div>

            </div>

            {{-- COMMENTS --}}
            <div class="ts-card" style="margin-top:18px">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Comments</div>
                        <div class="ts-sub">{{ $task->comments->count() }} comment(s)</div>
                    </div>
                </div>

                <div class="ts-body">

                    <form method="POST" action="{{ route('tenant.tasks.comments.store', $task->id) }}" class="mini-form">
                        @csrf
                        <textarea name="body" class="mini-input" rows="2" placeholder="Write a comment..." required maxlength="5000"></textarea>
                        <button type="submit" class="btn btn-secondary"><i class="ti ti-send"></i></button>
                    </form>

                    @forelse($task->comments as $comment)
                    <div class="cm-item">
                        <div class="cm-head">
                            <span class="cm-author">{{ $comment->user?->name ?? 'Unknown' }}</span>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="cm-time">{{ $comment->created_at->diffForHumans() }}</span>
                                @if($comment->user_id === auth()->id())
                                <form method="POST" action="{{ route('tenant.tasks.comments.destroy', [$task->id, $comment->id]) }}" data-confirm="Delete this comment?" data-confirm-ok="Delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="chk-del"><i class="ti ti-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <div class="cm-body">{{ $comment->body }}</div>
                    </div>
                    @empty
                    <div class="empty-hint">No comments yet.</div>
                    @endforelse

                </div>

            </div>

        </div>

        {{-- SIDEBAR --}}
        <div class="side-stack">

            {{-- ACTIVITY --}}
            <div class="ts-card">

                <div class="ts-head">

                    <div>

                        <div class="ts-title">
                            Activity Timeline
                        </div>

                        <div class="ts-sub">
                            Task lifecycle activity
                        </div>

                    </div>

                </div>

                <div class="ts-body">

                    <div class="timeline">

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Task Created
                            </div>

                            <div class="tl-date">
                                {{ $task->created_at->format('d M Y h:i A') }}
                            </div>

                        </div>

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Task Updated
                            </div>

                            <div class="tl-date">
                                {{ $task->updated_at->format('d M Y h:i A') }}
                            </div>

                        </div>

                        @if($task->due_at)

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Due Date Scheduled
                            </div>

                            <div class="tl-date">
                                {{ $task->due_at->format('d M Y') }}
                            </div>

                        </div>

                        @endif

                    </div>

                </div>

            </div>

            {{-- QUICK ACTION --}}
            <div class="ts-card">

                <div class="ts-head">

                    <div>

                        <div class="ts-title">
                            Quick Actions
                        </div>

                        <div class="ts-sub">
                            Fast task operations
                        </div>

                    </div>

                </div>

                <div class="ts-body">

                    <div style="display:flex;flex-direction:column;gap:10px">

                        <a href="{{ route('tenant.tasks.edit', $task->id) }}"
                           class="btn btn-primary">

                            <i class="ti ti-edit"></i>
                            Edit Task

                        </a>

                        <form method="POST"
                              action="{{ route('tenant.tasks.destroy', $task->id) }}"
                              data-confirm="Delete this task?" data-confirm-ok="Delete">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="btn btn-danger w-100">

                                <i class="ti ti-trash"></i>
                                Delete Task

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            {{-- WATCHERS --}}
            <div class="ts-card">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Watchers</div>
                        <div class="ts-sub">Notified on updates</div>
                    </div>
                </div>

                <div class="ts-body">

                    <form method="POST" action="{{ route('tenant.tasks.watchers.store', $task->id) }}" style="display:flex;gap:8px;margin-bottom:12px">
                        @csrf
                        <select name="user_id" class="mini-input" required>
                            <option value="">— Add watcher —</option>
                            @foreach($staffList as $staff)
                                @unless($task->watchers->contains('id', $staff->id))
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endunless
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary"><i class="ti ti-plus"></i></button>
                    </form>

                    @forelse($task->watchers as $watcher)
                    <span class="watcher-chip">
                        <span class="watcher-avatar">{{ strtoupper(substr($watcher->name, 0, 1)) }}</span>
                        {{ $watcher->name }}
                        <form method="POST" action="{{ route('tenant.tasks.watchers.destroy', [$task->id, $watcher->id]) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="watcher-remove"><i class="ti ti-x"></i></button>
                        </form>
                    </span>
                    @empty
                    <div class="empty-hint">No watchers yet.</div>
                    @endforelse

                </div>

            </div>

            {{-- AUDIT HISTORY --}}
            <div class="ts-card">

                <div class="ts-head">
                    <div>
                        <div class="ts-title">Audit History</div>
                        <div class="ts-sub">Field-level change log</div>
                    </div>
                </div>

                <div class="ts-body">

                    @forelse($auditLogs as $log)
                    <div class="cm-item">
                        <div class="cm-head">
                            <span class="cm-author">{{ ucfirst($log->action) }} by {{ $log->user?->name ?? 'System' }}</span>
                            <span class="cm-time">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        @if($log->action === 'updated' && !empty($log->changed_fields))
                        <div class="cm-body">
                            @foreach($log->changed_fields as $change)
                            <div>
                                <strong>{{ \Illuminate\Support\Str::headline($change['field']) }}:</strong>
                                {{ \Illuminate\Support\Str::limit((string) ($change['old'] ?? '—'), 30) }}
                                → {{ \Illuminate\Support\Str::limit((string) ($change['new'] ?? '—'), 30) }}
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="empty-hint">No audit history yet.</div>
                    @endforelse

                </div>

            </div>

        </div>

    </div>

</div>

@endsection