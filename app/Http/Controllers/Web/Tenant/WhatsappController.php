<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // Returns a ready-to-use sender for this tenant, or null if the tenant
    // hasn't connected the WhatsApp Cloud API yet (Settings > WhatsApp API).
    private function connectedService(): ?WhatsappChatbotService
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->exists || !$settings->is_connected) {
            return null;
        }

        return WhatsappChatbotService::forTenant($this->tenantId());
    }

    // Approved Meta templates the send forms can offer — empty unless this
    // tenant's number is linked through the WhatsApp Gateway.
    private function metaTemplates(): array
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->viaGateway() || !$settings->is_connected) {
            return [];
        }

        return WhatsappGatewayClient::approvedTemplates($settings->gateway_workspace_id);
    }

    // The picker posts "name|language" — find that template again server-side
    // (never trust the posted body).
    private function findMetaTemplate(string $key): ?array
    {
        foreach ($this->metaTemplates() as $template) {
            if ($template['name'] . '|' . $template['language'] === $key) {
                return $template;
            }
        }

        return null;
    }

    // One trimmed value per {{n}} in the template; null if any is left blank.
    private function templateParams(array $template, array $raw): ?array
    {
        $params = [];

        for ($i = 0; $i < $template['params']; $i++) {
            $value = trim((string) ($raw[$i] ?? ''));
            if ($value === '') {
                return null;
            }
            $params[] = $value;
        }

        return $params;
    }

    // ── Index — dashboard ─────────────────────────────────────────
    public function index(): View
    {
        $stats = [
            'total_sent'  => WhatsappLog::where('status', 'sent')->count(),
            'today'       => WhatsappLog::whereDate('created_at', today())->count(),
            'this_month'  => WhatsappLog::whereMonth('created_at', now()->month)->count(),
            'failed'      => WhatsappLog::where('status', 'failed')->count(),
            'templates'   => WhatsappTemplate::where('is_active', true)->count(),
        ];

        $recentLogs = WhatsappLog::with(['sentBy', 'template'])
            ->latest()->limit(5)->get();

        return view('tenant.whatsapp.index', compact('stats', 'recentLogs'));
    }

    // ── Templates — list ──────────────────────────────────────────
    public function templates(): View
    {
        $templates  = WhatsappTemplate::withCount('logs')->latest()->paginate(12);
        $categories = WhatsappTemplate::categories();

        $waSettings = WhatsappSetting::forTenant($this->tenantId());
        $metaTemplatesAvailable = $waSettings->viaGateway() && $waSettings->is_connected;

        return view('tenant.whatsapp.templates', compact('templates', 'categories', 'metaTemplatesAvailable'));
    }

    // ── Template — store ──────────────────────────────────────────
    public function storeTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(WhatsappTemplate::categories()))],
        ]);

        WhatsappTemplate::create([
            'tenant_id' => $this->tenantId(),
            'name'      => $request->name,
            'body'      => $request->body,
            'category'  => $request->category,
            'is_active' => true,
        ]);

        return back()->with('success', 'Template created successfully.');
    }

    // ── Template — update ─────────────────────────────────────────
    public function updateTemplate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(WhatsappTemplate::categories()))],
        ]);

        $template = WhatsappTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $template->update($request->only('name', 'body', 'category'));

        return back()->with('success', 'Template updated.');
    }

    // ── Template — delete ─────────────────────────────────────────
    public function deleteTemplate(int $id): RedirectResponse
    {
        WhatsappTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->delete();

        return back()->with('success', 'Template deleted.');
    }

    // ── Send — single ─────────────────────────────────────────────
    public function sendForm(Request $request): View
    {
        $templates = WhatsappTemplate::where('is_active', true)->orderBy('name')->get();
        $leads     = Lead::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'phone']);

        // Pre-fill if came from lead/contact
        $lead    = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $isConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        $metaTemplates = $this->metaTemplates();

        return view('tenant.whatsapp.send', compact(
            'templates', 'leads', 'contacts', 'lead', 'contact', 'isConnected', 'metaTemplates'
        ));
    }

    // ── Send — process single ─────────────────────────────────────
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'to_phone'    => ['required', 'string'],
            'to_name'     => ['nullable', 'string'],
            'message'     => ['required', 'string'],
            'lead_id'     => ['nullable', 'exists:leads,id'],
            'contact_id'  => ['nullable', 'exists:contacts,id'],
            'template_id' => ['nullable', 'exists:whatsapp_templates,id'],
            'attachment'  => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
            'meta_template'     => ['nullable', 'string', 'max:200'],
            'template_params'   => ['nullable', 'array'],
            'template_params.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = $this->connectedService();
        if (!$service) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        $metaTemplate = $params = null;
        if ($request->filled('meta_template')) {
            $metaTemplate = $this->findMetaTemplate($request->meta_template);
            if (!$metaTemplate) {
                return back()->withInput()->with('error', 'That WhatsApp template is not available — it may not be approved yet.');
            }
            if ($request->hasFile('attachment')) {
                return back()->withInput()->with('error', "An attachment can't be sent together with a template.");
            }
            $params = $this->templateParams($metaTemplate, $request->input('template_params', []));
            if ($params === null) {
                return back()->withInput()->with('error', 'Fill in every template field.');
            }
        }

        $waId = preg_replace('/[^0-9]/', '', $request->to_phone);

        if ($metaTemplate) {
            $ok    = $service->sendTemplate($waId, $metaTemplate['name'], $metaTemplate['language'], $params);
            $error = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the template message.');
            [$mediaType, $mediaId, $attachmentName] = [null, null, null];
            // The Logs page shows what the customer actually received.
            $message = WhatsappGatewayClient::renderTemplateBody($metaTemplate['body'], $params);
        } else {
            [$ok, $error, $mediaType, $mediaId, $attachmentName] = $this->deliver($service, $waId, $request->message, $request->file('attachment'));
            $message = $request->message;
        }

        WhatsappLog::create([
            'tenant_id'       => $this->tenantId(),
            'template_id'     => $request->template_id,
            'lead_id'         => $request->lead_id,
            'contact_id'      => $request->contact_id,
            'sent_by'         => auth()->id(),
            'to_phone'        => $request->to_phone,
            'to_name'         => $request->to_name,
            'message'         => $message,
            'status'          => $ok ? 'sent' : 'failed',
            'error_message'   => $error,
            'media_type'      => $mediaType,
            'media_id'        => $mediaId,
            'attachment_name' => $attachmentName,
            'wamid'           => $ok ? $service->lastMessageId : null,
            'sent_at'         => now(),
        ]);

        if (!$ok) {
            return back()->with('error', 'Failed to send WhatsApp message' . ($error ? ": {$error}" : '.'));
        }

        return redirect()->route('tenant.whatsapp.logs')->with('success', "Message sent to {$request->to_phone}.");
    }

    // Shared single-recipient delivery used by both send() and sendBulk() —
    // uploads the attachment (once, by the caller) or reuses an already
    // uploaded media id, then sends text or media accordingly.
    // Returns [ok, error, mediaType, mediaId, attachmentName].
    private function deliver(WhatsappChatbotService $service, string $waId, string $message, $file = null, ?string $preUploadedMediaId = null, ?string $preMediaType = null, ?string $preAttachmentName = null): array
    {
        if ($preUploadedMediaId) {
            $ok = $service->sendMediaMessage($waId, $preUploadedMediaId, $preMediaType, $message, $preAttachmentName);
            return [$ok, $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the media message.'), $preMediaType, $preUploadedMediaId, $preAttachmentName];
        }

        if ($file) {
            $mediaType = WhatsappChatbotService::mediaTypeForMime($file->getMimeType());
            $attachmentName = $file->getClientOriginalName();
            $mediaId = $service->uploadMedia($file->getRealPath(), $file->getMimeType());

            if (!$mediaId) {
                return [false, $service->lastError ?? 'Media upload failed.', $mediaType, null, $attachmentName];
            }

            $ok = $service->sendMediaMessage($waId, $mediaId, $mediaType, $message, $attachmentName);
            return [$ok, $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the media message.'), $mediaType, $mediaId, $attachmentName];
        }

        $ok = $service->sendMessage($waId, $message);
        return [$ok, $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the message.'), null, null, null];
    }

    // ── Bulk send form ────────────────────────────────────────────
    public function bulkForm(): View
    {
        $templates  = WhatsappTemplate::where('is_active', true)->orderBy('name')->get();
        $leads      = Lead::orderBy('name')->get(['id', 'name', 'phone', 'source', 'status']);
        $contacts   = Contact::orderBy('name')->get(['id', 'name', 'phone', 'company']);

        $isConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        $metaTemplates = $this->metaTemplates();

        return view('tenant.whatsapp.bulk', compact('templates', 'leads', 'contacts', 'isConnected', 'metaTemplates'));
    }

    // ── Bulk send — process ───────────────────────────────────────
    public function sendBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'recipients'  => ['required', 'array', 'min:1'],
            'message'     => ['required', 'string'],
            'template_id' => ['nullable', 'exists:whatsapp_templates,id'],
            'type'        => ['required', 'in:leads,contacts'],
            'attachment'  => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
            'meta_template'     => ['nullable', 'string', 'max:200'],
            'template_params'   => ['nullable', 'array'],
            'template_params.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = $this->connectedService();
        if (!$service) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        $metaTemplate = $rawParams = null;
        if ($request->filled('meta_template')) {
            $metaTemplate = $this->findMetaTemplate($request->meta_template);
            if (!$metaTemplate) {
                return back()->withInput()->with('error', 'That WhatsApp template is not available — it may not be approved yet.');
            }
            if ($request->hasFile('attachment')) {
                return back()->withInput()->with('error', "An attachment can't be sent together with a template.");
            }
            $rawParams = $this->templateParams($metaTemplate, $request->input('template_params', []));
            if ($rawParams === null) {
                return back()->withInput()->with('error', 'Fill in every template field.');
            }
        }

        // Upload the attachment once — the same media id is reused for every recipient below.
        $mediaId = $mediaType = $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mediaType = WhatsappChatbotService::mediaTypeForMime($file->getMimeType());
            $attachmentName = $file->getClientOriginalName();
            $mediaId = $service->uploadMedia($file->getRealPath(), $file->getMimeType());

            if (!$mediaId) {
                return back()->with('error', 'Media upload failed — bulk send cancelled.');
            }
        }

        $bulkId = Str::uuid();
        $today  = now()->format('d M Y');

        $sent = 0;
        $failed = 0;

        foreach ($request->recipients as $id) {
            $record = $request->type === 'leads'
                ? Lead::find($id)
                : Contact::find($id);

            if (!$record || !$record->phone) continue;

            $waId = preg_replace('/[^0-9]/', '', $record->phone);

            // Replace {{variables}} in the actual submitted message per recipient
            $variables = [
                'name'       => $record->name ?? '',
                'company'    => $record->company ?? '',
                'phone'      => $record->phone ?? '',
                'email'      => $record->email ?? '',
                'amount'     => $record->lead_value ?? '',
                'date'       => $today,
                'business'   => auth()->user()->tenant->name,
                'agent_name' => auth()->user()->name,
            ];
            $message = WhatsappTemplate::substituteVariables($request->message, $variables);

            if ($metaTemplate) {
                // Template fields take the same {{variables}}, filled per recipient.
                $params  = array_map(fn ($value) => WhatsappTemplate::substituteVariables($value, $variables), $rawParams);
                $ok      = $service->sendTemplate($waId, $metaTemplate['name'], $metaTemplate['language'], $params);
                $error   = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the template message.');
                $message = WhatsappGatewayClient::renderTemplateBody($metaTemplate['body'], $params);
            } else {
                [$ok, $error] = $this->deliver($service, $waId, $message, null, $mediaId, $mediaType, $attachmentName);
            }
            $ok ? $sent++ : $failed++;

            WhatsappLog::create([
                'tenant_id'       => $this->tenantId(),
                'template_id'     => $request->template_id,
                'lead_id'         => $request->type === 'leads' ? $id : null,
                'contact_id'      => $request->type === 'contacts' ? $id : null,
                'sent_by'         => auth()->id(),
                'to_phone'        => $record->phone,
                'to_name'         => $record->name,
                'message'         => $message,
                'status'          => $ok ? 'sent' : 'failed',
                'error_message'   => $error,
                'media_type'      => $mediaType,
                'media_id'        => $mediaId,
                'attachment_name' => $attachmentName,
                'wamid'           => $ok ? $service->lastMessageId : null,
                'is_bulk'         => true,
                'bulk_id'         => $bulkId,
                'sent_at'         => now(),
            ]);
        }

        return redirect()
            ->route('tenant.whatsapp.logs')
            ->with('success', "{$sent} messages sent." . ($failed > 0 ? " {$failed} failed." : ''));
    }

    // ── Logs ──────────────────────────────────────────────────────
    public function logs(Request $request): View
    {
        $query = WhatsappLog::with(['sentBy', 'template', 'lead', 'contact'])->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('is_bulk'))  $query->where('is_bulk', $request->is_bulk);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(20)->withQueryString();

        return view('tenant.whatsapp.logs', compact('logs'));
    }

    // ── Preview template (AJAX) ───────────────────────────────────
    public function previewTemplate(Request $request): JsonResponse
    {
        $template = WhatsappTemplate::where('id', $request->template_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $preview = $template->render([
            'name'       => 'Rahul Sharma',
            'company'    => 'Acme Corp',
            'phone'      => '+91 98765 43210',
            'business'   => auth()->user()->tenant->name,
            'agent_name' => auth()->user()->name,
            'date'       => now()->format('d M Y'),
        ]);

        return response()->json(['preview' => $preview]);
    }
}