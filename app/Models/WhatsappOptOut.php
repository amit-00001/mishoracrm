<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

// A number that must not get bulk / broadcast WhatsApp messages. One-to-one
// replies from the Inbox are not blocked — opting out is about marketing.
class WhatsappOptOut extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'phone', 'source'];

    // Words a customer can send to stop / resume broadcasts. Matched on the
    // whole message, case-insensitive, ignoring punctuation.
    public const STOP_WORDS  = ['stop', 'unsubscribe', 'stop all', 'optout', 'opt out', 'opt-out'];
    public const START_WORDS = ['start', 'subscribe', 'optin', 'opt in', 'opt-in'];

    public static function digits(?string $phone): string
    {
        return preg_replace('/[^0-9]/', '', (string) $phone);
    }

    public static function normalizeWord(?string $text): string
    {
        return trim(preg_replace('/[^a-z\- ]+/', '', strtolower((string) $text)));
    }

    public static function isStopWord(?string $text): bool
    {
        return in_array(static::normalizeWord($text), self::STOP_WORDS, true);
    }

    public static function isStartWord(?string $text): bool
    {
        return in_array(static::normalizeWord($text), self::START_WORDS, true);
    }

    public static function isOptedOut(int $tenantId, ?string $phone): bool
    {
        $digits = static::digits($phone);

        return $digits !== '' && static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('phone', $digits)->exists();
    }

    // Set of opted-out numbers (digits) for this tenant: [digits => true].
    public static function setFor(int $tenantId): array
    {
        return static::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->pluck('phone')->flip()->map(fn () => true)->all();
    }

    public static function add(int $tenantId, ?string $phone, string $source = 'keyword'): void
    {
        $digits = static::digits($phone);
        if ($digits === '') {
            return;
        }

        static::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId, 'phone' => $digits],
            ['source' => $source]
        );
    }

    public static function remove(int $tenantId, ?string $phone): void
    {
        static::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('phone', static::digits($phone))->delete();
    }
}
