<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    public function getNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return trim(implode(' ', $parts));
    }

    public function getDisplayFirstNameAttribute(): string
    {
        if (!empty($this->first_name)) {
            return $this->first_name;
        }
        $name = $this->name;
        return $name ? explode(' ', $name)[0] : 'User';
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'ceo'               => 'Chief Executive Officer',
            'admin'             => 'Administrator',
            'farmer'            => 'Farmer',
            'vet'               => 'Veterinarian',
            'agronomist'        => 'Agronomist',
            'agro-dealer'       => 'Agro Dealer',
            'extension-officer' => 'Extension Worker',
            'field-officer'     => 'Field Officer',
            'data-analyst'      => 'Data Analyst',
            'm-e-officer', 'me-officer', 'me_officer' => 'Monitoring & Evaluation Officer',
            'customer-support'  => 'Customer Support',
            'hr'                => 'Human Resources',
            'finance'           => 'Finance Officer',
            'operations'        => 'Operations Manager',
            default             => ucwords(str_replace('-', ' ', $this->role ?? 'Staff')),
        };
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->profile_photo)) {
            if (str_starts_with($this->profile_photo, 'http')) {
                return $this->profile_photo;
            }
            return config('app.url').'/storage/'.$this->profile_photo;
        }
        $name = $this->name ?: 'User';
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=0F6B3E&color=fff&bold=true&size=80';
    }

    /** Generate a new API token, store its hash, return the plain text token. */
    public function createToken(string $name = 'mobile'): object
    {
        $plain = bin2hex(random_bytes(32));
        $this->update(['api_token' => hash('sha256', $plain)]);
        return (object) ['plainTextToken' => $plain];
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function finances()
    {
        return $this->hasMany(Finance::class);
    }

    // ── Subscription Relationships ──────────────────────────────────────────

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('status', 'trial')->where('trial_ends_at', '>', now());
                })->orWhere(function ($q2) {
                    $q2->where('status', 'active')->where('ends_at', '>', now());
                });
            })
            ->first();
    }

    public function latestSubscription(): ?Subscription
    {
        return $this->subscriptions()->first();
    }

    public function currentPlan(): string
    {
        return $this->activeSubscription()?->plan ?? 'none';
    }

    public function subscriptionStatus(): string
    {
        $sub = $this->activeSubscription();
        if (!$sub) {
            $latest = $this->latestSubscription();
            return $latest?->status ?? 'none';
        }
        return $sub->status;
    }

    public function canAccess(string $feature): bool
    {
        // Non-farmer roles bypass subscription checks
        if ($this->role !== 'farmer') {
            return true;
        }
        return $this->activeSubscription()?->hasFeature($feature) ?? false;
    }

    public function hasActivePlan(string $minPlan = 'basic'): bool
    {
        $sub = $this->activeSubscription();
        if (!$sub) return false;
        $minLevel = config("subscription.plans.{$minPlan}.plan_level", 1);
        return $sub->planLevel() >= $minLevel;
    }

    // Start a trial subscription
    public function startTrial(string $plan = 'basic'): Subscription
    {
        $days = config("subscription.plans.{$plan}.trial_days", 14);
        return $this->subscriptions()->create([
            'plan'          => $plan,
            'status'        => 'trial',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays($days),
            'starts_at'     => now(),
            'ends_at'       => now()->addDays($days),
            'amount_paid'   => 0,
        ]);
    }

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
