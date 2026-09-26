<?php

namespace App\Services;

use App\Models\Deal;

// Shared create/update/stage-transition logic for deals, used by both the
// Web and API DealControllers so probability defaults, actual_close_date,
// and stage_changed_at tracking stay identical across both. Side effects
// (webhooks, notifications, quotation auto-accept) are handled separately
// via DealObserver + its listeners once these methods call Deal::update()/
// Deal::create() — no need to trigger them here.
class DealService
{
    public static function defaultProbability(string $stage): int
    {
        return match ($stage) {
            'new'         => 10,
            'proposal'    => 30,
            'negotiation' => 60,
            'won'         => 100,
            'lost'        => 0,
            default       => 10,
        };
    }

    // ── Create a new deal ───────────────────────────────────────────
    public static function create(array $data, int $tenantId, int $createdBy): Deal
    {
        $data['tenant_id']  = $tenantId;
        $data['created_by'] = $createdBy;

        if (empty($data['probability'])) {
            $data['probability'] = self::defaultProbability($data['stage']);
        }

        if ($data['stage'] === 'won' && empty($data['actual_close_date'])) {
            $data['actual_close_date'] = now()->toDateString();
        }

        $data['stage_changed_at'] = now();

        return Deal::create($data);
    }

    // ── Update an existing deal from a form/API payload ─────────────
    // Partial-safe: keys missing from $data keep their stored value.
    public static function update(Deal $deal, array $data): Deal
    {
        $stage        = $data['stage'] ?? $deal->stage;
        $stageChanged = $stage !== $deal->stage;

        if ($stage === 'won' && $stageChanged) {
            $data['actual_close_date'] = now()->toDateString();
        }

        if ($stageChanged) {
            $data['stage_changed_at'] = now();
        }

        $data = self::resolveProbability($data, $deal, $stage, $stageChanged);

        $deal->update($data);

        return $deal;
    }

    // The edit form always re-posts the stored probability, so "non-empty" does
    // not mean "the user chose this". Rules:
    //  - a Won/Lost deal is 100%/0% by definition, whatever was posted;
    //  - moving to another stage with the probability left untouched adopts the
    //    new stage's default (otherwise Won kept the old 10%);
    //  - an explicit, different probability on an open stage is respected;
    //  - a blank probability falls back to the stage default;
    //  - a payload without the key leaves the stored value alone.
    private static function resolveProbability(array $data, Deal $deal, string $stage, bool $stageChanged): array
    {
        if (in_array($stage, ['won', 'lost'], true)) {
            $data['probability'] = self::defaultProbability($stage);

            return $data;
        }

        if (array_key_exists('probability', $data)) {
            $posted = $data['probability'];

            if ($posted === null || $posted === '' || ($stageChanged && (int) $posted === (int) $deal->probability)) {
                $data['probability'] = self::defaultProbability($stage);
            }
        } elseif ($stageChanged) {
            $data['probability'] = self::defaultProbability($stage);
        }

        return $data;
    }

    // ── Move a deal to a new stage (Kanban drag / quick action) ─────
    public static function updateStage(Deal $deal, string $stage, ?string $lostReason = null): Deal
    {
        $data = [
            'stage'            => $stage,
            'probability'      => self::defaultProbability($stage),
            'stage_changed_at' => now(),
        ];

        if ($stage === 'won') {
            $data['actual_close_date'] = now()->toDateString();
        }

        if ($stage === 'lost' && $lostReason) {
            $data['lost_reason'] = $lostReason;
        }

        $deal->update($data);

        return $deal;
    }

    public static function markWon(Deal $deal): Deal
    {
        $deal->update([
            'stage'             => 'won',
            'probability'       => 100,
            'actual_close_date' => now()->toDateString(),
            'stage_changed_at'  => now(),
        ]);

        return $deal;
    }

    public static function markLost(Deal $deal, ?string $reason): Deal
    {
        $deal->update([
            'stage'             => 'lost',
            'probability'       => 0,
            'actual_close_date' => now()->toDateString(),
            'lost_reason'       => $reason,
            'stage_changed_at'  => now(),
        ]);

        return $deal;
    }
}
