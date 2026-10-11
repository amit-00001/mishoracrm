<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\BelongsToTenant;

class WhatsappChatbotFlow extends Model
{
    use BelongsToTenant;

    public const ACTIONS = ['loyalty_join', 'loyalty_balance', 'book_appointment', 'raise_ticket'];
    public const REPLY_TYPES = ['text', 'buttons', 'list', 'link'];
    public const MEDIA_TYPES = ['image', 'video', 'document'];

    protected $fillable = [
        'tenant_id',
        'name',
        'trigger_keywords',
        'keyword_match',
        'response_message',
        'action',
        'quick_replies',
        'is_default',
        'is_active',
        'triggered_count',
        'sort_order',
        'canvas_x',
        'canvas_y',
        'reply_type',
        'header_text',
        'footer_text',
        'list_button_text',
        'media_type',
        'media_url',
        'is_welcome',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'quick_replies'    => 'array',
        'is_default'       => 'boolean',
        'is_welcome'       => 'boolean',
        'is_active'        => 'boolean',
        'triggered_count'  => 'integer',
        'canvas_x'         => 'integer',
        'canvas_y'         => 'integer',
    ];

    // text | buttons | list | link. Flows saved before reply types existed
    // have none: a flow with quick replies was always a buttons message.
    public function effectiveReplyType(): string
    {
        if (in_array($this->reply_type, self::REPLY_TYPES, true)) {
            return $this->reply_type;
        }

        return empty($this->quick_replies) ? 'text' : 'buttons';
    }

    // Lower-case, punctuation / emoji stripped, spaces collapsed — so
    // "Hi!!", "HI " and "hi" are the same word, in any script (Hindi too).
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    // How well $text matches this flow's keywords: 0 = not at all, higher =
    // more specific (exact > whole phrase / word > part of a longer word >
    // typo). The best-scoring flow answers, so "refund price" beats "price".
    public function score(string $text): int
    {
        $text = static::normalize($text);
        if ($text === '') return 0;

        $words = explode(' ', $text);
        $best  = 0;

        foreach ($this->trigger_keywords ?? [] as $keyword) {
            $keyword = static::normalize((string) $keyword);
            if ($keyword === '') continue;

            $len = mb_strlen($keyword);

            if ($text === $keyword) {
                $best = max($best, 1000 + $len);
                continue;
            }

            if ($this->keyword_match === 'exact') continue;

            // Whole word / whole phrase.
            if (str_contains(" {$text} ", " {$keyword} ")) {
                $best = max($best, 500 + $len);
                continue;
            }

            // Inside a longer word ("pric" in "pricing"). Short keywords like
            // "hi" would hit "this" / "which", so they only match as words.
            if ($len >= 4 && str_contains($text, $keyword)) {
                $best = max($best, 300 + $len);
                continue;
            }

            // A typo: "pricee", "prise", "helo", "namastey".
            if ($len >= 4 && !str_contains($keyword, ' ')) {
                $allowed = $len >= 8 ? 2 : 1;
                foreach ($words as $word) {
                    if (abs(mb_strlen($word) - $len) <= $allowed && levenshtein($word, $keyword) <= $allowed) {
                        $best = max($best, 100 + $len);
                        break;
                    }
                }
            }
        }

        return $best;
    }

    public function matches(string $text): bool
    {
        if (empty($this->trigger_keywords)) return (bool) $this->is_default;

        return $this->score($text) > 0;
    }

    // The flow that should answer $text among $flows (already active and
    // tenant-scoped): highest score wins, the canvas order breaks a tie.
    // Default and welcome flows are fallbacks, never keyword-matched.
    // Returns ['flow' => …, 'score' => …] or null.
    public static function best(iterable $flows, string $text): ?array
    {
        $winner = null;
        $top    = 0;

        foreach ($flows as $flow) {
            if ($flow->is_default || $flow->is_welcome) continue;

            $score = $flow->score($text);
            if ($score > $top) {
                $winner = $flow;
                $top    = $score;
            }
        }

        return $winner ? ['flow' => $winner, 'score' => $top] : null;
    }

    public function incrementTriggered(): void
    {
        $this->increment('triggered_count');
    }
}
