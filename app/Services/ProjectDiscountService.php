<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDiscount;

/**
 * Replaces a project's discounts/deductions and recomputes its final
 * amount. Discount totals are derived exclusively from the stored rows
 * so `amount` stays consistent everywhere it is consumed.
 */
class ProjectDiscountService
{
    /**
     * Delete the project's existing discounts and store the given rows.
     *
     * @param  array<int, array<string, mixed>>  $discounts
     */
    public function sync(Project $project, array $discounts): void
    {
        $project->discounts()->delete();

        foreach ($discounts as $discount) {
            $project->discounts()->create([
                'type' => $discount['type'],
                'mode' => $discount['mode'],
                'amount' => $discount['mode'] === ProjectDiscount::MODE_AMOUNT
                    ? (int) $discount['amount']
                    : null,
                'percentage' => $discount['mode'] === ProjectDiscount::MODE_PERCENTAGE
                    ? $discount['percentage']
                    : null,
                'title' => $discount['title'],
                'description' => $discount['description'] ?? null,
            ]);
        }

        $project->recalculateAmount();
    }
}
