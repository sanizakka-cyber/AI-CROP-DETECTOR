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
        'is_active', 'is_verified', 'force_password_reset',
        'email_verified_at', 'phone_verified_at',
        'expo_push_token', 'fcm_token', 'api_token',
        'application_status', 'rejection_reason', 'reviewed_at', 'reviewed_by',
        'is_test_account',
    ];

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDocument::class);
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
            'm-e-officer', 'me-officer', 'me_officer' => 'Monitoring & Evaluation Officer',
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
            'force_password_reset' => 'boolean',
            'is_active'            => 'boolean',
            'is_verified'          => 'boolean',
            'is_test_account'      => 'boolean',
        ];
    }
}
