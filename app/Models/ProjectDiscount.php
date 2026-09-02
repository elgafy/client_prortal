<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDiscount extends Model
{
    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_DEDUCTION = 'deduction';

    public const MODE_AMOUNT = 'amount';

    public const MODE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'project_id',
        'type',
        'mode',
        'amount',
        'percentage',
        'title',
        'description',
    ];

    /** @var list<string> */
    protected $appends = ['value'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'percentage' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The discount's value in whole currency units for the given subtotal.
     * Percentages are rounded half away from zero.
     */
    public function valueFor(int $subtotal): int
    {
        if ($this->mode === self::MODE_PERCENTAGE) {
            return (int) round($subtotal * (float) $this->percentage / 100);
        }

        return (int) $this->amount;
    }

    /**
     * The discount's computed value in whole currency units for its project.
     */
    public function getValueAttribute(): int
    {
        return $this->valueFor((int) $this->project->subtotal);
    }
}
