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

    protected $fillable = [
        'first_name', 'middle_name', 'last_name',
        'email', 'phone', 'role', 'state', 'lga', 'ward', 'village', 'country',
        'password', 'profile_photo', 'language', 'last_seen',
        'is_active', 'is_verified', 'is_pilot', 'force_password_reset',
        'email_verified_at', 'phone_verified_at',
        'expo_push_token', 'fcm_token',
        'application_status', 'rejection_reason', 'reviewed_at', 'reviewed_by',
        'onboarding_dismissed_at', 'rider_status',
        'consent_given_at', 'data_export_requested_at', 'data_export_completed_at',
        'referral_code', 'referred_by',
        'notification_email',
    ];

    // api_token, is_test_account, two_factor_code, two_factor_expires_at are intentionally
    // excluded from $fillable — they are set only via direct property assignment by trusted code.

    // Route all Laravel notification-system emails through notification_email when set.
    // This lets test accounts funnel OTPs to a shared QA inbox without changing their login email.
    public function routeNotificationForMail($notification = null): string
    {
        return $this->notification_email ?? $this->email;
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Wallet::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function diagnoses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Diagnosis::class);
    }

    public function consultations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Consultation::class, 'farmer_id');
    }

    public function isPending(): bool
    {
        return ($this->application_status ?? 'approved') === 'pending';
    }

    public function isApproved(): bool
    {
        return ($this->application_status ?? 'approved') === 'approved';
    }

    public function requiresApproval(): bool
    {
        return !in_array($this->role ?? 'farmer', ['farmer', 'general-user', 'ceo', 'admin']);
    }

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
            'ceo'                  => 'Chief Executive Officer',
            'admin'                => 'Administrator',
            'farmer'               => 'Farmer',
            'vet'                  => 'Veterinarian',
            'agronomist'           => 'Agronomist',
            'agro-dealer'          => 'Agro Dealer',
            'equipment-dealer'     => 'Equipment Dealer',
            'agribusiness-owner'   => 'Agribusiness Owner',
            'cooperative'          => 'Cooperative',
            'government-agency'    => 'Government Agency',
            'ngo'                  => 'NGO',
            'research-institution' => 'Research Institution',
            'input-supplier'       => 'Input Supplier',
            'logistics-provider'   => 'Logistics Provider',
            'investor'             => 'Investor',
            'general-user'         => 'General User',
            'extension-officer'    => 'Extension Worker',
            'field-officer'        => 'Field Officer',
            'data-analyst'         => 'Data Analyst',
            'm-e-officer', 'me-officer', 'me_officer', 'monitoring-evaluation' => 'Monitoring & Evaluation Officer',
            'customer-support'     => 'Customer Support',
            'hr'                   => 'Human Resources',
            'finance'              => 'Finance Officer',
            'operations'           => 'Operations Manager',
            default                => ucwords(str_replace('-', ' ', $this->role ?? 'Staff')),
        };
    }

    public function getStaffIdAttribute(): string
    {
        $codes = [
            'ceo'                  => 'CEO',
            'admin'                => 'ADM',
            'farmer'               => 'FMR',
            'vet'                  => 'VET',
            'agronomist'           => 'AGR',
            'agro-dealer'          => 'ACD',
            'equipment-dealer'     => 'EQD',
            'agribusiness-owner'   => 'ABO',
            'cooperative'          => 'COP',
            'government-agency'    => 'GOV',
            'ngo'                  => 'NGO',
            'research-institution' => 'RES',
            'input-supplier'       => 'INS',
            'logistics-provider'   => 'LOG',
            'investor'             => 'INV',
            'general-user'         => 'GEN',
            'extension-officer'    => 'EXT',
            'field-officer'        => 'FLD',
            'data-analyst'         => 'DAT',
            'm-e-officer'          => 'MEO',
            'me-officer'           => 'MEO',
            'me_officer'           => 'MEO',
            'customer-support'     => 'CSP',
            'hr'                   => 'HRS',
            'finance'              => 'FIN',
            'operations'           => 'OPS',
        ];
        $code = $codes[$this->role] ?? strtoupper(substr($this->role ?? 'STF', 0, 3));
        $year = $this->created_at ? $this->created_at->format('Y') : now()->format('Y');
        $seq  = str_pad($this->id, 4, '0', STR_PAD_LEFT);
        return "{$code}-{$year}-{$seq}";
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->profile_photo)) {
            if (str_starts_with($this->profile_photo, 'http')) {
                return $this->profile_photo;
            }
            return config('app.url').'/storage/'.$this->profile_photo;
        }

        // Self-contained SVG initials — no external dependency, never produces a broken image
        $name    = $this->name ?: 'User';
        $words   = array_values(array_filter(explode(' ', trim($name))));
        $initials = count($words) >= 2
            ? strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1))
            : strtoupper(substr($name, 0, 2));

        $bg  = $this->role === 'ceo' ? '#0B2447' : '#0F6B3E';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
            . '<rect width="80" height="80" rx="12" fill="' . $bg . '"/>'
            . '<text x="40" y="40" font-family="Arial,sans-serif" font-size="28" font-weight="700" '
            . 'fill="#ffffff" text-anchor="middle" dominant-baseline="central">'
            . htmlspecialchars($initials, ENT_XML1)
            . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** Generate a new API token, store its hash, return the plain text token. */
    public function createToken(string $name = 'mobile'): object
    {
        $plain = bin2hex(random_bytes(32));
        // api_token is excluded from $fillable — use direct assignment so the DB is written
        $this->api_token = hash('sha256', $plain);
        $this->saveQuietly();
        return (object) ['plainTextToken' => $plain];
    }

    // ── RBAC Relationships ─────────────────────────────────────────────────────

    public function staffRoles()
    {
        return $this->belongsToMany(StaffRole::class, 'staff_role_assignments')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    public function activeStaffRoles()
    {
        return $this->staffRoles()->where('is_active', true);
    }

    public function hasRbacPermission(string $module, string $ability): bool
    {
        return $this->activeStaffRoles->some(fn ($r) => $r->hasPermission($module, $ability));
    }

    public function getActiveStaffRolesAttribute()
    {
        return $this->staffRoles()->where('is_active', true)->get();
    }

    // ── Other Relationships ────────────────────────────────────────────────────

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function finances()
    {
        return $this->hasMany(Finance::class);
    }

    // ── Payment Relationships ───────────────────────────────────────────────

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest();
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
            'email_verified_at'    => 'datetime',
            'phone_verified_at'    => 'datetime',
            'force_password_reset'   => 'boolean',
            'is_active'              => 'boolean',
            'is_verified'            => 'boolean',
            'is_pilot'               => 'boolean',
            'is_test_account'        => 'boolean',
            'onboarding_dismissed_at'  => 'datetime',
            'consent_given_at'         => 'datetime',
            'data_export_requested_at' => 'datetime',
            'data_export_completed_at' => 'datetime',
            'nps_rated_at'             => 'datetime',
            'nps_score'                => 'integer',
        ];
    }
}
