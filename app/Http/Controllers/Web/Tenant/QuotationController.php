<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Exports\QuotationsExport;
use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationTermsTemplate;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\EmailService;
use App\Services\QuotationService;
use App\Services\WhatsappChatbotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class QuotationController extends Controller
{
    // ── Find quotation — tenant scope ─────────────────────────────
    private function findQuotation(int|string $id): Quotation
    {
        return Quotation::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Shared filtered query (index page + export reuse this) ────
    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Quotation::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'quotations', $user, 'created_by');

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('number', 'like', "%{$filters['search']}%")
                    ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = self::filteredQuery(auth()->user()->tenant_id, $request->all(), auth()->user())
            ->with(['contact', 'lead', 'createdBy'])
            ->latest();

        $quotations = $query->paginate(15)->withQueryString();

        // Summary counts — single grouped query instead of one COUNT per status
        $summary = ViewScope::apply(Quotation::where('tenant_id', auth()->user()->tenant_id), 'quotations', auth()->user(), 'created_by')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'all'      => $summary->sum(),
            'draft'    => $summary->get('draft', 0),
            'sent'     => $summary->get('sent', 0),
            'accepted' => $summary->get('accepted', 0),
            'rejected' => $summary->get('rejected', 0),
        ];

        $statuses = Quotation::statuses();

        return view('tenant.quotations.index', compact(
            'quotations',
            'counts',
            'statuses'
        ));
    }

    // ── Export (respects current index filters) ────────────────────
    public function export(Request $request)
    {
        return Excel::download(
            new QuotationsExport(auth()->user()->tenant_id, $request->query(), auth()->user()),
            'quotations_export_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $this->authorize('create', Quotation::class);

        $contacts = Contact::orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $leads    = Lead::orderBy('name')->get(['id', 'name', 'phone']);

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $deal = $request->filled('deal_id')
            ? Deal::where('id', $request->deal_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        if ($deal) {
            $contact = $contact ?? $deal->contact;
            $lead    = $lead ?? $deal->lead;
        }

        $number    = Quotation::generateNumber();
        $statuses  = Quotation::statuses();
        $tenant    = auth()->user()->tenant;
        $products  = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','product_code','name','description','rate','tax_percent','hsn','unit']);
        $services  = Service::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit','is_package']);
        $currencies = config('quotation.currencies');
        $templates  = QuotationTermsTemplate::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name', 'terms', 'notes']);

        return view('tenant.quotations.create', compact(
            'contacts',
            'leads',
            'contact',
            'lead',
            'deal',
            'number',
            'statuses',
            'tenant',
            'products',
            'services',
            'currencies',
            'templates'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(QuotationRequest $request): RedirectResponse
    {
        $this->authorize('create', Quotation::class);

        $quotation = QuotationService::store($request->validated(), auth()->user()->tenant_id, auth()->id());

        return redirect()
            ->route('tenant.quotations.show', $quotation->id)
            ->with('success', "Quotation {$quotation->number} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        $quotation->load(['contact.employees', 'lead', 'deal', 'createdBy', 'invoice', 'parentQuotation', 'revisions']);

        $tenant   = auth()->user()->tenant;
        $statuses = Quotation::statuses();

        return view('tenant.quotations.show', compact('quotation', 'tenant', 'statuses'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id)
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('modify', $quotation);

        if ($quotation->status === 'accepted') {
            return redirect()
                ->route('tenant.quotations.show', $quotation->id)
                ->with('error', 'Accepted quotation cannot be edited.');
        }

        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $leads     = Lead::orderBy('name')->get(['id', 'name']);
        $statuses  = Quotation::statuses();
        $tenant    = auth()->user()->tenant;
        $products  = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','product_code','name','description','rate','tax_percent','hsn','unit']);
        $services  = Service::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit','is_package']);
        $currencies = config('quotation.currencies');
        $templates  = QuotationTermsTemplate::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name', 'terms', 'notes']);

        return view('tenant.quotations.edit', compact(
            'quotation',
            'contacts',
            'leads',
            'statuses',
            'tenant',
            'products',
            'services',
            'currencies',
            'templates'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(QuotationRequest $request, int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('modify', $quotation);

        QuotationService::update($quotation, $request->validated());

        $invoice = $quotation->status === 'accepted' ? QuotationService::accept($quotation) : null;

        $message = $invoice
            ? "Quotation updated successfully. Invoice {$invoice->number} created automatically."
            : 'Quotation updated successfully.';

        return redirect()
            ->route('tenant.quotations.show', $quotation->id)
            ->with('success', $message);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('delete', $quotation);
        $number    = $quotation->number;
        $quotation->delete();

        return redirect()
            ->route('tenant.quotations.index')
            ->with('success', "Quotation {$number} deleted.");
    }

    // ── Update status ─────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
        ]);

        $quotation = $this->findQuotation($id);
        $this->authorize('modify', $quotation);
        $quotation->update(['status' => $request->status]);

        $invoice = $quotation->status === 'accepted' ? QuotationService::accept($quotation) : null;

        $message = $invoice
            ? "Quotation status updated. Invoice {$invoice->number} created automatically."
            : 'Quotation status updated.';

        return back()->with('success', $message);
    }

    // ── Download PDF ──────────────────────────────────────────────
    public function pdf(int|string $id)
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        $quotation->load(['contact', 'createdBy']);
        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('tenant.quotations.pdf', compact('quotation', 'tenant'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Quotation-{$quotation->number}.pdf");
    }

    // ── Send via email ────────────────────────────────────────────
    // To = contact's primary contact (primary employee's email, or the
    // contact's own email if no primary employee is set). Cc = every
    // other known email (other employees, contact's own email if unused).
    public function send(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        abort_unless(auth()->user()->user_type === 'superadmin' || auth()->user()->can('quotations.send'), 403);
        $quotation->load(['contact.employees', 'createdBy']);

        $contact = $quotation->contact;
        $toEmail = $contact?->primaryEmail();

        if (!$toEmail) {
            return back()->with('error', 'Contact has no email address.');
        }

        $toName = $contact->employees->firstWhere('is_primary', true)?->name ?: $contact->name;
        $cc     = $contact->ccEmails();

        $tenant  = auth()->user()->tenant;
        $subject = "Quotation {$quotation->number} from {$tenant->name}";
        $html    = "<p>Dear {$toName},</p>"
            . "<p>Please find attached quotation <strong>{$quotation->number}</strong> for "
            . "<strong>" . $quotation->currencySymbol() . number_format($quotation->total, 2) . "</strong>.</p>"
            . "<p><a href=\"{$quotation->publicUrl()}\">Click here to view and accept/reject this quotation online</a>.</p>"
            . "<p>Thank you for your interest.</p><p>{$tenant->name}</p>";

        $pdfContent = Pdf::loadView('tenant.quotations.pdf', compact('quotation', 'tenant'))
            ->setPaper('a4', 'portrait')
            ->output();

        $attachments = [[
            'content' => $pdfContent,
            'name'    => "Quotation-{$quotation->number}.pdf",
            'mime'    => 'application/pdf',
        ]];

        $sent = EmailService::send($quotation->tenant_id, $toEmail, $toName, $subject, $html, $attachments, $cc);

        if (!$sent) {
            try {
                Mail::send([], [], function ($mail) use ($toEmail, $toName, $cc, $subject, $html, $pdfContent, $quotation) {
                    $mail->to($toEmail, $toName)
                         ->subject($subject)
                         ->html($html)
                         ->attachData($pdfContent, "Quotation-{$quotation->number}.pdf", ['mime' => 'application/pdf']);

                    foreach ($cc as $ccRecipient) {
                        $mail->cc($ccRecipient['email'], $ccRecipient['name'] ?? null);
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', "Could not send email: {$e->getMessage()}");
            }
        }

        if ($quotation->status === 'draft') {
            $quotation->update(['status' => 'sent']);
        }

        $ccNote = count($cc) ? ' (cc: ' . count($cc) . ')' : '';

        return back()->with('success', "Quotation sent to {$toEmail}{$ccNote}.");
    }

    // ── Send via WhatsApp ─────────────────────────────────────────
    public function sendWhatsapp(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        abort_unless(auth()->user()->user_type === 'superadmin' || auth()->user()->can('quotations.send'), 403);
        $quotation->load('contact');

        $contact = $quotation->contact;
        if (!$contact?->phone) {
            return back()->with('error', 'Contact has no phone number.');
        }

        $settings = WhatsappSetting::forTenant($quotation->tenant_id);
        if (!$settings->exists || !$settings->is_connected) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        $service = WhatsappChatbotService::forTenant($quotation->tenant_id);
        $waId    = preg_replace('/[^0-9]/', '', $contact->phone);
        $tenant  = auth()->user()->tenant;

        $message = "Hi {$contact->name}, please find your quotation {$quotation->number} from {$tenant->name} for "
            . $quotation->currencySymbol() . number_format($quotation->total, 2)
            . ". View and accept/reject here: {$quotation->publicUrl()}";

        $pdfContent = Pdf::loadView('tenant.quotations.pdf', compact('quotation', 'tenant'))
            ->setPaper('a4', 'portrait')
            ->output();

        $tmpPath = tempnam(sys_get_temp_dir(), 'quo') . '.pdf';
        file_put_contents($tmpPath, $pdfContent);

        $mediaId = $service->uploadMedia($tmpPath, 'application/pdf');
        $ok      = false;
        $error   = $service->lastError;

        if ($mediaId) {
            $ok    = $service->sendMediaMessage($waId, $mediaId, 'document', $message, "Quotation-{$quotation->number}.pdf");
            $error = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the media message.');
        }

        @unlink($tmpPath);

        // Follow-up interactive message — lets the customer accept/reject right
        // inside WhatsApp (see WhatsappWebhookController for the qacc_/qrej_ handling)
        // instead of only linking out to the public page.
        if ($ok && !$quotation->hasCustomerResponded()) {
            $token = $quotation->ensurePublicToken();
            $service->sendInteractiveButtons(
                $waId,
                "Do you accept quotation {$quotation->number}?",
                [
                    ['title' => 'Accept', 'reply_id' => "qacc_{$token}"],
                    ['title' => 'Reject', 'reply_id' => "qrej_{$token}"],
                ]
            );
        }

        WhatsappLog::create([
            'tenant_id'       => $quotation->tenant_id,
            'contact_id'      => $contact->id,
            'sent_by'         => auth()->id(),
            'to_phone'        => $contact->phone,
            'to_name'         => $contact->name,
            'message'         => $message,
            'status'          => $ok ? 'sent' : 'failed',
            'error_message'   => $error,
            'media_type'      => 'document',
            'media_id'        => $mediaId,
            'attachment_name' => "Quotation-{$quotation->number}.pdf",
            'sent_at'         => now(),
        ]);

        if (!$ok) {
            return back()->with('error', 'Failed to send WhatsApp message' . ($error ? ": {$error}" : '.'));
        }

        if ($quotation->status === 'draft') {
            $quotation->update(['status' => 'sent']);
        }

        return back()->with('success', "Quotation sent to {$contact->phone} via WhatsApp.");
    }

    // ── Convert to Invoice ────────────────────────────────────────
    public function convertToInvoice(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);

        if ($quotation->invoice) {
            return redirect()
                ->route('tenant.invoices.show', $quotation->invoice->id)
                ->with('info', 'Invoice already exists for this quotation.');
        }

        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Only accepted quotations can be converted to invoice.');
        }

        $invoice = QuotationService::accept($quotation);

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', "Invoice {$invoice->number} created from quotation {$quotation->number}.");
    }

    // ── Create a new draft version cloned from this quotation ──────
    public function newVersion(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        $this->authorize('create', Quotation::class);

        $newVersion = QuotationService::createNewVersion($quotation, auth()->id());

        return redirect()
            ->route('tenant.quotations.edit', $newVersion->id)
            ->with('success', "Version {$newVersion->version} ({$newVersion->number}) created from {$quotation->number}. Review and save when ready.");
    }

    public function quotationData(string $tenant, int|string $quotation): JsonResponse
    {
        $quotation = $this->findQuotation($quotation);
        $this->authorize('view', $quotation);

        $quotation->load('contact');

        return response()->json([
            'success' => true,

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Basic
                |--------------------------------------------------------------------------
                */

                'quotation_id' => $quotation->id,

                'contact_id' => $quotation->contact_id,

                'customer' => [
                    'name'        => $quotation->contact?->name,
                    'company'     => $quotation->contact?->company,
                    'phone'       => $quotation->contact?->phone,
                    'email'       => $quotation->contact?->email,
                    'gst_number'  => $quotation->contact?->gst_number,
                    'address'     => $quotation->contact?->address,
                    'city'        => $quotation->contact?->city,
                    'state'       => $quotation->contact?->state,
                    'pincode'     => $quotation->contact?->pincode,
                ],

                /*
                |--------------------------------------------------------------------------
                | Invoice Financials
                |--------------------------------------------------------------------------
                */

                'items'        => $quotation->items ?? [],
                'subtotal'     => $quotation->subtotal ?? 0,
                'discount'     => $quotation->discount ?? 0,
                'tax_percent'  => $quotation->tax_percent ?? 18,
                'tax_amount'   => $quotation->tax_amount ?? 0,
                'total'        => $quotation->total ?? 0,

                /*
                |--------------------------------------------------------------------------
                | Terms
                |--------------------------------------------------------------------------
                */

                'notes' => $quotation->notes,
                'terms' => $quotation->terms,

                /*
                |--------------------------------------------------------------------------
                | Suggested Dates
                |--------------------------------------------------------------------------
                */

                'invoice_date' => now()->format('Y-m-d'),
                'due_date'     => now()->addDays(7)->format('Y-m-d'),
            ],
        ]);
    }
}
