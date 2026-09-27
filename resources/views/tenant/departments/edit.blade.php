@extends('layouts.app')
@section('title', 'Edit Department')

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.departments.index') }}" style="color:var(--text-300);text-decoration:none">Departments</a>
            <span style="margin:0 6px">›</span> Edit
        </div>
        <div class="page-title">Edit Department</div>
    </div>
    <a href="{{ route('tenant.departments.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:560px">
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
        <form method="POST" action="{{ route('tenant.departments.update', $department->id) }}">
            @csrf
            @method('PUT')

            <div style="padding:24px;display:flex;flex-direction:column;gap:18px">

                {{-- Name --}}
                <div style="display:flex;flex-direction:column;gap:7px">
                    <label style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        Department Name <span style="color:var(--red)">*</span>
                    </label>
                    <input type="text" name="name"
                           style="padding:10px 13px;background:var(--bg-input);border:1.5px solid {{ $errors->has('name') ? 'var(--red)':'var(--border-default)' }};border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;transition:border-color .15s"
                           placeholder="e.g. Sales, Marketing, IT..."
                           value="{{ old('name', $department->name) }}" required/>
                    @error('name')
                    <span style="font-size:12px;color:var(--red)">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Description --}}
                <div style="display:flex;flex-direction:column;gap:7px">
                    <label style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        Description
                    </label>
                    <textarea name="description" rows="3"
                              style="padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;resize:vertical"
                              placeholder="What does this department do...">{{ old('description', $department->description) }}</textarea>
                </div>

                {{-- Staff count info --}}
                <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-sm)">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px;color:var(--accent);flex-shrink:0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    <span style="font-size:13px;color:var(--text-200)">
                        <strong style="color:var(--text-100)">{{ $department->staff_count }}</strong>
                        staff member{{ $department->staff_count !== 1 ? 's' : '' }} in this department
                    </span>
                </div>

            </div>

            {{-- Actions --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle)">

                <div></div>

                <div style="display:flex;gap:10px">
                    <a href="{{ route('tenant.departments.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Update Department
                    </button>
                </div>

            </div>
        </form>
    </div>

    @if($department->staff_count === 0)
    <div style="margin-top:14px;padding:14px 16px;background:var(--bg-surface);border:1px solid rgba(255,82,87,.3);border-radius:var(--r-lg);display:flex;align-items:center;justify-content:space-between;gap:12px">
        <span style="font-size:12px;color:var(--text-400)">Delete this department. This cannot be undone.</span>
        <form method="POST" action="{{ route('tenant.departments.destroy', $department->id) }}"
              data-confirm="Delete {{ $department->name }}? This cannot be undone." data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button type="submit"
                    style="padding:8px 14px;background:var(--red-dim);color:var(--red);border:1.5px solid rgba(255,82,87,.3);border-radius:var(--r-sm);font-size:12.5px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Delete Department
            </button>
        </form>
    </div>
    @else
    <div style="margin-top:14px;font-size:12px;color:var(--text-400)">
        Assign staff to another dept before deleting
    </div>
    @endif
</div>

@endsection