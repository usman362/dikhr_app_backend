<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_premium',
        'trial_started_at',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_premium' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withPivot('role')->withTimestamps();
    }

    public function dhikrContributions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DhikrContribution::class);
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    /**
     * Whether the user is currently inside their 7-day free trial.
     *
     * Trial is the post-registration grace period before the user has to
     * subscribe. Set when the user registers; never extended after.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Days remaining in the user's trial — 0 if the trial has ended or
     * was never started. Useful for showing "X days left" banners.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        // Round up so a partial day still shows as a full day remaining,
        // matching the "you have 3 days left" UX expectation.
        return (int) ceil(now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * The single gate the API + middleware use to decide whether the
     * user can use the app at all.
     *
     * Subscription-only model: app is locked unless the user is either
     *   (a) on an active 7-day trial, OR
     *   (b) has an active paid subscription (`is_premium`).
     * Admins always have access (so they can manage the app even if
     * their own subscription lapses).
     */
    public function hasActiveAccess(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->isPremium() || $this->isOnTrial();
    }

    /**
     * Start the 7-day free trial. Idempotent: if the user already has a
     * trial set (existing user, or someone tampering with the field), we
     * do NOT reset it — the trial is a one-time grant per account so
     * users can't get another 7 days by re-registering or similar.
     */
    public function startTrialIfNeeded(): void
    {
        if ($this->trial_started_at !== null) {
            return;
        }

        $this->forceFill([
            'trial_started_at' => now(),
            'trial_ends_at'    => now()->addDays(7),
        ])->save();
    }

    public function deviceTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
