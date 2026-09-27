<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ErrorLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'exception_class',
        'http_status',
        'message',
        'url',
        'method',
        'stack_trace',
        'request_data',
        'ip_address',
        'user_agent',
        'is_resolved',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'is_resolved'  => 'boolean',
        'created_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getStatusColorAttribute(): string
    {
        return match (true) {
            $this->http_status >= 500 => 'red',
            $this->http_status >= 400 => 'amber',
            default                   => 'gray',
        };
    }

    public function getShortClassAttribute(): string
    {
        return $this->exception_class ? class_basename($this->exception_class) : 'Exception';
    }

    private static function truncateStrings(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = mb_substr($value, 0, 500);
            }
        });

        return $data;
    }

    public static function capture(\Throwable $e, $request = null): void
    {
        try {
            $request = $request ?? request();
            $status  = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            // 404s are too noisy for bots — skip unless a user is logged in
            if ($status === 404 && !auth()->check()) return;

            $safeInput = [];
            if ($request) {
                // Truncate at every depth: a nested base64 blob (e.g. a screenshot inside an
                // array field) would otherwise blow past max_allowed_packet and the insert —
                // and with it the record of the original error — would be lost.
                $safeInput = static::truncateStrings(
                    $request->except(['password', 'password_confirmation', 'token', '_token'])
                );
            }

            static::create([
                'tenant_id'       => auth()->check() ? auth()->user()->tenant_id : null,
                'user_id'         => auth()->id(),
                'exception_class' => get_class($e),
                'http_status'     => $status,
                'message'         => mb_substr($e->getMessage(), 0, 1000),
                'url'             => $request?->fullUrl(),
                'method'          => $request?->method(),
                'stack_trace'     => mb_substr($e->getTraceAsString(), 0, 5000),
                'request_data'    => $safeInput ?: null,
                'ip_address'      => $request?->ip(),
                'user_agent'      => mb_substr($request?->userAgent() ?? '', 0, 255),
                'created_at'      => now(),
            ]);
        } catch (\Throwable) {
            // Never let error logging break the app
        }
    }
}
