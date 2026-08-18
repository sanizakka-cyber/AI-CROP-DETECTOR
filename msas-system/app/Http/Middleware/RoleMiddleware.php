<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Canonicalized comparison: a route that lists only one spelling of
        // a role with known synonyms (e.g. 'monitoring-evaluation') still
        // correctly admits a user stored under a different spelling of the
        // same role (e.g. 'm-e-officer', what every seeder actually assigns)
        // instead of silently denying them.
        if (!Roles::isAny($user->role, $roles)) {
            // Redirect based on role if they try to access unauthorized area
            $redirectMap = [
                'ceo'                    => 'ceo.dashboard',
                'admin'                  => 'admin.dashboard',
                'farmer'                 => 'farmer.dashboard',
                'vet'                    => 'vet.dashboard',
                'agronomist'             => 'agronomist.dashboard',
                'agro-dealer'            => 'dealer.dashboard',
                'equipment-dealer'       => 'equipment-dealer.dashboard',
                'cooperative'            => 'cooperative.dashboard',
                'ngo'                    => 'ngo.dashboard',
                'government'             => 'government.dashboard',
                'research-institution'   => 'research-institution.dashboard',
                'investor'               => 'investor.dashboard',
                'financial-institution'  => 'financial-institution.dashboard',
                'extension-officer'      => 'extension.dashboard',
                'finance'                => 'finance.dashboard',
                'hr'                     => 'hr.dashboard',
                'operations'             => 'operations.dashboard',
                'data-analyst'           => 'data-analyst.dashboard',
                'm-e-officer'            => 'monitoring-evaluation.dashboard',
                'me-officer'             => 'monitoring-evaluation.dashboard',
                'monitoring-evaluation'  => 'monitoring-evaluation.dashboard',
                'field-officer'          => 'field-officer.dashboard',
                'customer-support'       => 'customer-support.dashboard',
                'rider'                  => 'rider.dashboard',
                'agribusiness-owner'     => 'agribusiness.dashboard',
                'input-supplier'         => 'input-supplier.dashboard',
                'logistics-provider'     => 'logistics.dashboard',
                'government-agency'      => 'government.dashboard',
                'researcher'             => 'research-institution.dashboard',
                'student'                => 'farmer.dashboard',
                'general-user'           => 'dashboard',
            ];
            $routeName = $redirectMap[Roles::canonical($user->role)] ?? $redirectMap[$user->role] ?? null;
            if ($routeName) {
                try {
                    return redirect()->route($routeName)->with('error', 'Unauthorized access.');
                } catch (\Exception $e) {
                    // route may not exist yet
                }
            }
            return abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
