<?php

namespace App\Http\Controllers;

use App\Services\LegalConsentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Please accept our updated Terms and Privacy Policy" page for signed-in
 * users who have not accepted the current versions yet.
 */
class LegalAcceptanceController extends Controller
{
    public function __construct(private LegalConsentService $legal) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->legal->needsReacceptance($request->user())) {
            return redirect()->intended(route('dashboard'));
        }

        return view('legal.accept', [
            'termsVersion'   => $this->legal->version('terms'),
            'privacyVersion' => $this->legal->version('privacy'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['terms_accepted' => ['accepted']],
            ['terms_accepted.accepted' => __('You must agree to the Terms of Service and Privacy Policy to continue.')]
        );

        $this->legal->recordReacceptance($request->user(), $request);

        return redirect()->intended(route('dashboard'))
            ->with('success', __('Thank you. Your agreement has been recorded.'));
    }
}
