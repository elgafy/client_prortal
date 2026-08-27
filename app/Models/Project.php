<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
