<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationReceivedMail;
use App\Models\AuditLog;
use App\Models\UserDocument;
use App\Services\OtpService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private OtpService $otp, private RegistrationService $registration) {}

    public function create(): View
    {
        $plans           = config('subscription.plans');
        $preselectedPlan = request('plan');
        $validPlans      = array_keys($plans);
        if (!in_array($preselectedPlan, $validPlans)) {
            $preselectedPlan = '';
        }

        return view('auth.register', compact('plans', 'preselectedPlan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $publicRoles = RegistrationService::PUBLIC_ROLES;

        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'identifier'  => 'required|string|max:255',
            'role'        => 'nullable|string|in:' . implode(',', $publicRoles),
            'country'     => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'lga'         => 'nullable|string|max:100',
            'ward'        => 'nullable|string|max:100',
            'invite_code' => 'nullable|string|max:20',
            'ref'         => 'nullable|string|max:20',
            'password'    => ['required', 'confirmed', Rules\Password::min(8)
                ->mixedCase()->numbers()->symbols()],
            'documents.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        try {
            ['type' => $identifierType, 'value' => $identifier] = $this->registration->resolveIdentifier($request->identifier);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['identifier' => $e->getMessage()]);
        }
        $isEmail = $identifierType === 'email';
        $isPhone = $identifierType === 'phone';

        [$user, $pendingPlan, $needsApproval] = $this->registration->createAccount(
            [
                'first_name'  => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name'   => $request->last_name,
                'role'        => $request->role,
                'country'     => $request->country,
                'state'       => $request->state,
                'lga'         => $request->lga,
                'ward'        => $request->ward,
                'password'    => $request->password,
                'invite_code' => $request->invite_code,
                'ref'         => $request->ref,
                'plan'        => $request->input('plan', ''),
            ],
            $identifierType,
            $identifier,
            function ($user, $role) use ($request) {
                // Store uploaded documents (base64 in DB — survives Render ephemeral wipes)
                if ($request->hasFile('documents')) {
                    $docLabels = $this->getDocumentLabels($role);

                    foreach ($request->file('documents') as $key => $file) {
                        if (! $file || ! $file->isValid()) {
                            continue;
                        }

                        $docType = is_string($key) ? $key : 'document_' . ($key + 1);
                        $label   = $docLabels[$docType] ?? ucwords(str_replace('_', ' ', $docType));

                        UserDocument::create([
                            'user_id'        => $user->id,
                            'document_type'  => $docType,
                            'document_label' => $label,
                            'original_name'  => $file->getClientOriginalName(),
                            'mime_type'      => $file->getMimeType(),
                            'file_size'      => $file->getSize(),
                            'content_base64' => base64_encode(file_get_contents($file->getRealPath())),
                        ]);
                    }
                }
            }
        );

        // Non-farmer roles: pending approval path
        if ($needsApproval) {
            if ($isEmail) {
                try {
                    Mail::to($user->email)->send(new ApplicationReceivedMail($user));
                } catch (\Exception $e) {
                    Log::error('ApplicationReceivedMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            return redirect()->route('application.submitted');
        }

        // Farmer / general-user: existing flow continues below

        if ($isPhone) {
            Auth::login($user);
            $request->session()->regenerate();
            Log::info('Phone-only registration completed', ['user_id' => $user->id]);
            // No email available for phone-only users at this point

            if ($pendingPlan && $user->role === 'farmer') {
                return redirect()->route('subscription.plans')
                    ->with('success', 'Welcome to MSAS FarmAI! Start your free 14-day trial below.');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to MSAS FarmAI! Your account has been created.');
        }

        // Email farmer: OTP verification flow
        $plain     = $this->otp->generate($identifier, 'registration');
        $verifyUrl = URL::temporarySignedRoute(
            'verification.link',
            now()->addMinutes(OtpService::TTL_MINUTES),
            ['user' => $user->id]
        );
        $emailFailed = ! $this->otp->sendViaEmail($identifier, $plain, $user->first_name, $user->id, 'registration', $verifyUrl);
        $expiresAt   = $this->otp->expiresAt($identifier, 'registration');

        try {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'otp.sent',
                'model'      => 'User',
                'model_id'   => $user->id,
                'details'    => json_encode(['context' => 'registration', 'delivered' => ! $emailFailed]),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable) {}

        $request->session()->put([
            'otp_context'         => 'registration',
            'otp_identifier'      => $identifier,
            'otp_user_id'         => $user->id,
            'otp_sms_failed'      => false,
            'otp_email_failed'    => $emailFailed,
            'otp_expires_at'      => $expiresAt?->toISOString(),
            'otp_delivery_method' => 'email',
            'pending_plan'        => $pendingPlan,
        ]);

        return redirect()->route('otp.verify');
    }

    private function getDocumentLabels(string $role): array
    {
        return match($role) {
            'vet'                  => ['vet_license' => 'Veterinary License', 'accreditation' => 'Professional Accreditation'],
            'agronomist'           => ['professional_license' => 'Professional License', 'proof_of_qualification' => 'Proof of Qualification'],
            'agro-dealer'          => ['cac_registration' => 'CAC / Business Registration'],
            'equipment-dealer'     => ['business_registration' => 'Business Registration Certificate'],
            'agribusiness-owner'   => ['company_registration' => 'Company Registration Certificate'],
            'cooperative'          => ['cooperative_certificate' => 'Cooperative Certificate', 'members_list' => 'Members List (min. 5)'],
            'government-agency'    => ['official_documents' => 'Official Government Documentation'],
            'ngo'                  => ['registration_cert' => 'Registration Certificate', 'tax_exemption' => 'Tax Exemption Certificate'],
            'research-institution' => ['institutional_affiliation' => 'Institutional Affiliation Letter', 'research_proposal' => 'Research Proposal'],
            'input-supplier'       => ['cac_registration' => 'CAC / Business Registration'],
            'logistics-provider'   => ['transport_license' => 'Transport / Haulage License'],
            'investor'             => ['id_document' => 'Valid ID', 'investment_profile' => 'Investment Profile / Portfolio'],
            default                => [],
        };
    }
}
