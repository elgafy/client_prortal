<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'client_id',
        'project_id',
        'amount',
        'currency',
        'payment_date',
        'method',
        'received_from',
        'received_by',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Payments that count toward balances — only active ones (PRD §52).
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive($query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }
}
