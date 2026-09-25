<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\Sql;
use App\Models\WorkflowRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = WorkflowRequest::with(['tenant', 'user', 'template'])
            ->orderByRaw(Sql::fieldOrder('status', ['new', 'in_progress', 'completed', 'rejected']))
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();
        $newCount = WorkflowRequest::new()->count();

        return view('superadmin.workflow-requests.index', compact('requests', 'newCount'));
    }

    public function show(WorkflowRequest $workflowRequest): View
    {
        $workflowRequest->load(['tenant', 'user', 'template']);
        return view('superadmin.workflow-requests.show', ['req' => $workflowRequest]);
    }

    public function updateStatus(Request $request, WorkflowRequest $workflowRequest): RedirectResponse
    {
        $request->validate([
            'status'      => ['required', 'in:new,in_progress,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workflowRequest->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Request updated.');
    }
}
