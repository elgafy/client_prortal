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
        'amount',
        'currency',
        'project_date',
        'status',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'project_date' => 'date:Y-m-d',
        ];
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
