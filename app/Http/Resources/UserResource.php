<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Canonical user shape returned by /register, /login, /me.
 *
 * Includes trial + subscription state so the mobile router can decide
 * paywall vs app on a SINGLE response, without needing a follow-up
 * round-trip to /subscription/status. Also keeps the JSON shape stable
 * — any new field gets added here so every endpoint that returns a
 * user stays consistent.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'is_admin'   => (bool) $this->is_admin,
            'is_premium' => (bool) $this->is_premium,

            // Trial info — null when the user has never started a trial
            // (only possible for legacy accounts; the migration backfills
            // everyone, and registration starts one immediately).
            'trial_started_at'     => optional($this->trial_started_at)->toIso8601String(),
            'trial_ends_at'        => optional($this->trial_ends_at)->toIso8601String(),
            'is_on_trial'          => $this->isOnTrial(),
            'trial_days_remaining' => $this->trialDaysRemaining(),

            // The single boolean the mobile router checks to decide
            // app vs paywall — duplicated here so /me alone is enough
            // to make that call (no extra request needed).
            'has_active_access' => $this->hasActiveAccess(),

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
