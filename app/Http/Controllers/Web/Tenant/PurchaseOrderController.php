<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Exports\PurchaseOrdersExport;
use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderReceiveRequest;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\EmailService;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    // ── Find purchase order — tenant scope ──────────────────────────
    private function findPurchaseOrder(int|string $id): PurchaseOrder
    {
        return PurchaseOrder::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Shared filtered query (index page + export reuse this) ──────
    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = PurchaseOrder::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'purchase_orders', $user, 'created_by');

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('number', 'like', "%{$filters['search']}%")
                    ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"));
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
            ->with(['vendor', 'purchaseRequest', 'createdBy'])
            ->latest();

        $purchaseOrders = $query->paginate(15)->withQueryString();

        $summary = ViewScope::apply(PurchaseOrder::where('tenant_id', auth()->user()->tenant_id), 'purchase_orders', auth()->user(), 'created_by')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'all'                 => $summary->sum(),
            'draft'               => $summary->get('draft', 0),
            'sent'                => $summary->get('sent', 0),
            'partially_received'  => $summary->get('partially_received', 0),
            'received'            => $summary->get('received', 0),
            'cancelled'           => $summary->get('cancelled', 0),
        ];

        $statuses = PurchaseOrder::statuses();

        return view('tenant.purchase-orders.index', compact('purchaseOrders', 'counts', 'statuses'));
    }

    // ── Export (respects current index filters) ─────────────────────
    public function export(Request $request)
    {
        return Excel::download(
            new PurchaseOrdersExport(auth()->user()->tenant_id, $request->query(), auth()->user()),
            'purchase_orders_export_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $this->authorize('create', PurchaseOrder::class);

        $vendors  = Vendor::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'rate', 'cost_price', 'tax_percent', 'hsn', 'unit']);

        $purchaseRequest = $request->filled('purchase_request_id')
            ? PurchaseRequest::where('id', $request->purchase_request_id)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first()
            : null;

        $number = PurchaseOrder::generateNumber();

        return view('tenant.purchase-orders.create', compact('vendors', 'products', 'purchaseRequest', 'number'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $purchaseOrder = PurchaseOrderService::store($request->validated(), auth()->user()->tenant_id, auth()->id());

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', "Purchase Order {$purchaseOrder->number} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['vendor', 'purchaseRequest', 'createdBy', 'vendorQuotes.vendor', 'vendorBills', 'goodsReceiptNotes.createdBy']);

        $vendors = ($purchaseOrder->status === 'draft' && !$purchaseOrder->vendor_id)
            ? Vendor::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name', 'company'])
            : collect();

        return view('tenant.purchase-orders.show', compact('purchaseOrder', 'vendors'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id)
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return redirect()
                ->route('tenant.purchase-orders.show', $purchaseOrder->id)
                ->with('error', ucfirst(str_replace('_', ' ', $purchaseOrder->status)) . ' purchase order cannot be edited.');
        }

        $vendors  = Vendor::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'rate', 'cost_price', 'tax_percent', 'hsn', 'unit']);

        return view('tenant.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'products'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(PurchaseOrderRequest $request, int|string $id): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);

        PurchaseOrderService::update($purchaseOrder, $request->validated());

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Purchase Order updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('delete', $purchaseOrder);
        $number = $purchaseOrder->number;
        $purchaseOrder->delete();

        return redirect()
            ->route('tenant.purchase-orders.index')
            ->with('success', "Purchase Order {$number} deleted.");
    }

    // ── Update status — manual transitions restricted to draft/sent/
    // cancelled only; partially_received/received are only reachable via
    // the receive() action. Moving to sent requires a vendor to be set.
    // Cancellation is allowed from any non-terminal status. ──────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,sent,cancelled'],
        ]);

        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);

        if ($request->status === 'sent' && !$purchaseOrder->vendor_id) {
            return back()->with('error', 'Select a vendor before marking this purchase order as Sent.');
        }

        $purchaseOrder->update(['status' => $request->status]);

        return back()->with('success', 'Purchase Order status updated.');
    }

    // ── Receive — logs one Goods Receipt Note (GRN). Records received &
    // accepted qty per line; only the accepted qty is credited to stock. ──
    public function receive(PurchaseOrderReceiveRequest $request, int|string $id): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('receive', $purchaseOrder);

        $data  = $request->validated();
        $lines = collect($data['items'])->map(fn ($row) => [
            'received_qty'     => $row['received_quantity'] ?? 0,
            'accepted_qty'     => $row['accepted_quantity'] ?? null,
            'rejection_reason' => $row['rejection_reason'] ?? null,
            'batch_number'     => $row['batch_number'] ?? null,
            'expiry_date'      => $row['expiry_date'] ?? null,
        ])->toArray();

        $grn = GoodsReceiptService::record(
            $purchaseOrder,
            $lines,
            auth()->id(),
            $data['received_date'] ?? null,
            $data['note'] ?? null
        );

        $msg = "Goods Receipt {$grn->number} recorded.";
        if ($grn->hasRejections()) {
            $msg .= " {$grn->totalRejected()} unit(s) rejected — follow up with the vendor.";
        }

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', $msg);
    }

    // ── Goods Receipt Note detail ─────────────────────────────────
    public function showGrn(int|string $id, int|string $grnId)
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('view', $purchaseOrder);

        $grn = $purchaseOrder->goodsReceiptNotes()
            ->with('createdBy')
            ->where('id', $grnId)
            ->firstOrFail();

        return view('tenant.purchase-orders.grn', compact('purchaseOrder', 'grn'));
    }

    // ── Download PDF ──────────────────────────────────────────────
    public function pdf(int|string $id)
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['vendor', 'createdBy']);
        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('tenant.purchase-orders.pdf', compact('purchaseOrder', 'tenant'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("PurchaseOrder-{$purchaseOrder->number}.pdf");
    }

    // ── Send via email — direct to the vendor (no employees/primary-
    // contact concept to resolve, simpler than Contact's version). ──
    public function send(int|string $id): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('view', $purchaseOrder);
        abort_unless(auth()->user()->user_type === 'superadmin' || auth()->user()->can('purchase_orders.send'), 403);
        $purchaseOrder->load(['vendor', 'createdBy']);

        $vendor  = $purchaseOrder->vendor;
        $toEmail = $vendor?->email;

        if (!$toEmail) {
            return back()->with('error', 'Vendor has no email address.');
        }

        $tenant  = auth()->user()->tenant;
        $subject = "Purchase Order {$purchaseOrder->number} from {$tenant->name}";
        $html    = "<p>Dear {$vendor->name},</p>"
            . "<p>Please find attached purchase order <strong>{$purchaseOrder->number}</strong> for "
            . "<strong>₹" . number_format($purchaseOrder->total, 2) . "</strong>.</p>"
            . "<p>Thank you.</p><p>{$tenant->name}</p>";

        $pdfContent = Pdf::loadView('tenant.purchase-orders.pdf', compact('purchaseOrder', 'tenant'))
            ->setPaper('a4', 'portrait')
            ->output();

        $attachments = [[
            'content' => $pdfContent,
            'name'    => "PurchaseOrder-{$purchaseOrder->number}.pdf",
            'mime'    => 'application/pdf',
        ]];

        $sent = EmailService::send($purchaseOrder->tenant_id, $toEmail, $vendor->name, $subject, $html, $attachments);

        if (!$sent) {
            try {
                Mail::send([], [], function ($mail) use ($toEmail, $vendor, $subject, $html, $pdfContent, $purchaseOrder) {
                    $mail->to($toEmail, $vendor->name)
                         ->subject($subject)
                         ->html($html)
                         ->attachData($pdfContent, "PurchaseOrder-{$purchaseOrder->number}.pdf", ['mime' => 'application/pdf']);
                });
            } catch (\Exception $e) {
                return back()->with('error', "Could not send email: {$e->getMessage()}");
            }
        }

        if ($purchaseOrder->status === 'draft') {
            $purchaseOrder->update(['status' => 'sent']);
        }

        return back()->with('success', "Purchase Order sent to {$toEmail}.");
    }
}
