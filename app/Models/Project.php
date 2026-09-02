<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'client_id',
        'name',
        'description',
        'subtotal',
        'currency',
        'project_date',
        'status',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'amount' => 'integer',
            'project_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        // A freshly created project has no discounts yet, so its final
        // amount starts equal to the subtotal. The discount service
        // recalculates it whenever discounts are synced.
        static::creating(function (Project $project) {
            $project->amount ??= $project->subtotal;
            $project->discount_total ??= 0;
        });
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ProjectDiscount, $this> */
    public function discounts(): HasMany
    {
        return $this->hasMany(ProjectDiscount::class);
    }

    /**
     * Recompute the persisted discount total and final amount from the
     * project's discounts. The final amount never drops below zero.
     */
    public function recalculateAmount(): void
    {
        $discountTotal = (int) $this->discounts()->get()->sum(
            fn (ProjectDiscount $discount) => $discount->valueFor($this->subtotal),
        );

        $this->forceFill([
            'discount_total' => $discountTotal,
            'amount' => max($this->subtotal - $discountTotal, 0),
        ])->save();
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Projects that count toward the client's outstanding balance (PRD §9).
     *
     * @param  Builder<self>  $query
     */
    public function scopeNotCancelled($query): void
    {
        $query->where('status', '!=', self::STATUS_CANCELLED);
    }
}
