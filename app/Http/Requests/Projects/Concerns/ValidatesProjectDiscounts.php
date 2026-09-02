<?php

namespace App\Http\Requests\Projects\Concerns;

use App\Models\ProjectDiscount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for the discount/deduction rows submitted with a project.
 * Each row is either a fixed amount or a percentage of the subtotal, and
 * the combined total may never exceed the subtotal.
 */
trait ValidatesProjectDiscounts
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function discountRules(): array
    {
        return [
            'discounts' => ['nullable', 'array', 'max:25'],
            'discounts.*.title' => ['required', 'string', 'max:255'],
            'discounts.*.type' => ['required', 'in:'.implode(',', [
                ProjectDiscount::TYPE_DISCOUNT,
                ProjectDiscount::TYPE_DEDUCTION,
            ])],
            'discounts.*.mode' => ['required', 'in:'.implode(',', [
                ProjectDiscount::MODE_AMOUNT,
                ProjectDiscount::MODE_PERCENTAGE,
            ])],
            'discounts.*.amount' => [
                'nullable',
                'required_if:discounts.*.mode,'.ProjectDiscount::MODE_AMOUNT,
                'integer',
                'min:1',
                'max:999999999999',
            ],
            'discounts.*.percentage' => [
                'nullable',
                'required_if:discounts.*.mode,'.ProjectDiscount::MODE_PERCENTAGE,
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'discounts.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * The computed value of every discount must stay within the subtotal.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            $subtotal = (int) ($data['subtotal'] ?? 0);
            $total = 0;

            foreach ((array) ($data['discounts'] ?? []) as $discount) {
                if (! is_array($discount)) {
                    continue;
                }

                $total += ($discount['mode'] ?? null) === ProjectDiscount::MODE_PERCENTAGE
                    ? (int) round($subtotal * (float) ($discount['percentage'] ?? 0) / 100)
                    : (int) ($discount['amount'] ?? 0);
            }

            if ($total > $subtotal) {
                $validator->errors()->add(
                    'discounts',
                    __('The combined discounts cannot exceed the project subtotal.'),
                );
            }
        });
    }
}
