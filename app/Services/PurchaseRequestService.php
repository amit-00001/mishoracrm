<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    // ── Create — collision-safe number generation + idempotent by token ──
    // number is a unique column generated from max(id)+1; two concurrent
    // submissions can race and compute the same number, so retry a few
    // times on a unique-constraint violation rather than 500ing.
    //
    // When the form supplies a submission token, (tenant_id, submission_token)
    // is unique: a repeated submit of the same form (double-click, browser
    // retry, slow response) returns the request that was already created
    // instead of inserting a duplicate. Callers can tell the two apart via
    // $purchaseRequest->wasRecentlyCreated.
    public static function store(array $data, int $tenantId, int $userId): PurchaseRequest
    {
        $token = $data['submission_token'] ?? null;
        unset($data['submission_token']);

        return retry(3, function () use ($data, $tenantId, $userId, $token) {
            $attributes = array_merge($data, [
                'number'       => PurchaseRequest::generateNumber(),
                'requested_by' => $userId,
                'status'       => 'pending',
            ]);

            if ($token === null || $token === '') {
                return PurchaseRequest::create($attributes + ['tenant_id' => $tenantId]);
            }

            return PurchaseRequest::createOrFirst(
                ['tenant_id' => $tenantId, 'submission_token' => $token],
                $attributes
            );
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — only reachable while pending, enforced by policy ──
    public static function update(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        // The token identifies the original create submission and must never change.
        unset($data['submission_token']);

        $purchaseRequest->update($data);

        return $purchaseRequest;
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }

    // ── Approve — flip to approved→converted, auto-create draft PO ──
    public static function approve(PurchaseRequest $purchaseRequest, int $approvedByUserId): ?PurchaseOrder
    {
        if ($purchaseRequest->purchaseOrder) {
            return null;
        }

        $purchaseRequest->update([
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => now(),
        ]);

        $items = collect($purchaseRequest->items ?? [])->map(fn ($item) => [
            'product_id'        => $item['product_id'] ?? null,
            'name'               => $item['name'] ?? '',
            'description'        => $item['description'] ?? null,
            'quantity'           => $item['quantity'] ?? 0,
            'rate'               => 0,
            'tax_percent'        => 0,
            'amount'             => 0,
            'received_quantity'  => 0,
        ])->toArray();

        $purchaseOrder = retry(3, function () use ($purchaseRequest, $items, $approvedByUserId) {
            return PurchaseOrder::create([
                'tenant_id'            => $purchaseRequest->tenant_id,
                'vendor_id'            => null,
                'purchase_request_id'  => $purchaseRequest->id,
                'number'               => PurchaseOrder::generateNumber(),
                'date'                 => now()->toDateString(),
                'items'                => $items,
                'subtotal'             => 0,
                'discount'             => 0,
                'tax_percent'          => 0,
                'tax_amount'           => 0,
                'total'                => 0,
                'status'               => 'draft',
                'created_by'           => $approvedByUserId,
            ]);
        }, 50, fn ($e) => static::isNumberCollision($e));

        $purchaseRequest->update(['status' => 'converted']);

        return $purchaseOrder;
    }

    // ── Reject — flip to rejected, store reason ─────────────────────
    public static function reject(PurchaseRequest $purchaseRequest, ?string $reason): PurchaseRequest
    {
        $purchaseRequest->update([
            'status'            => 'rejected',
            'rejection_reason'  => $reason,
        ]);

        return $purchaseRequest;
    }
}
