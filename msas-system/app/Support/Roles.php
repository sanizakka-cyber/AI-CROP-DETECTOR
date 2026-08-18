<?php

namespace App\Support;

/**
 * Canonical role-string resolution. There is no single enum/config for
 * roles across this codebase (role strings are redeclared in ~8 separate
 * places) — this class doesn't replace all of them, but it does fix the
 * actual bug class the audit found: one role (Monitoring & Evaluation
 * officer) has accumulated four different spellings across the codebase
 * over time ('m-e-officer', 'me-officer', 'monitoring-evaluation',
 * 'me_officer'), and every account seeder assigns only 'm-e-officer' —
 * so any role check that enumerated an incomplete subset of those
 * spellings silently denied real accounts. Route this class of check
 * through here instead of writing out every known spelling by hand.
 */
final class Roles
{
    /**
     * Canonical role, keyed by every known historical spelling for it.
     * Only roles that have accumulated more than one spelling need an
     * entry — every other role string is already its own canonical form.
     */
    private const SYNONYMS = [
        'm-e-officer'           => 'm-e-officer',
        'me-officer'            => 'm-e-officer',
        'monitoring-evaluation' => 'm-e-officer',
        'me_officer'            => 'm-e-officer',
    ];

    /** Canonical form of a role string (identity if it has no known synonym). */
    public static function canonical(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        return self::SYNONYMS[$role] ?? $role;
    }

    /** True if $role (any known spelling) canonically equals $canonicalRole. */
    public static function is(?string $role, string $canonicalRole): bool
    {
        return self::canonical($role) === self::canonical($canonicalRole);
    }

    /** True if $role (any known spelling) canonically matches any of $roles (any spelling). */
    public static function isAny(?string $role, array $roles): bool
    {
        $canonical = self::canonical($role);

        return $canonical !== null && in_array($canonical, array_map([self::class, 'canonical'], $roles), true);
    }
}
