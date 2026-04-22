<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Local mirror of RevenueCat subscription state.
 *
 * Updated via RevenueCat webhooks (Phase 5).
 */
class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'rc_customer_id',
        'store',
        'product_id',
        'store_transaction_id',
        'status',
        'current_period_start',
        'current_period_end',
        'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end'   => 'datetime',
            'auto_renew'           => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->status === 'grace_period';
    }
}
