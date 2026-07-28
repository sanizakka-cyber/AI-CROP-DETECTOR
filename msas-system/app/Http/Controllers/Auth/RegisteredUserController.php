<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\InviteCode;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use NormalizesPhone;

    public function __construct(private OtpService $otp) {}

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
        $publicRoles = [
            'farmer', 'vet', 'agronomist', 'agro-dealer',
            'equipment-dealer', 'agribusiness-owner', 'cooperative',
            'government-agency', 'ngo', 'research-institution',
            'input-supplier', 'logistics-provider', 'investor', 'general-user',
        ];

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
            'password'    => ['required', 'confirmed', Rules\Password::min(8)
                ->mixedCase()->numbers()->symbols()],
            'documents.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isPhone    = ! $isEmail && $this->looksLikePhone($identifier);

        if (! $isEmail && ! $isPhone) {
            return back()->withInput()->withErrors([
                'identifier' => 'Enter a valid email address or phone number (e.g. 08012345678 or +2348012345678).',
            ]);
        }

        if ($isEmail && User::where('email', $identifier)->exists()) {
            // Generic message to avoid confirming account existence to an attacker
            return back()->withInput()->withErrors([
                'identifier' => 'There is already an account associated with this identifier. Try signing in.',
            ]);
        }

        $normalizedPhone = $isPhone ? $this->normalizePhone($identifier) : null;

        if ($isPhone && User::where('phone', $normalizedPhone)->exists()) {
            return back()->withInput()->withErrors([
                'identifier' => 'There is already an account associated with this identifier. Try signing in.',
            ]);
        }

        $role              = in_array($request->role, $publicRoles) ? $request->role : 'farmer';
        $needsApproval     = ! in_array($role, ['farmer', 'general-user']);

        $userData = [
            'first_name'         => $request->first_name,
            'middle_name'        => $request->middle_name,
            'last_name'          => $request->last_name,
            'role'               => $role,
            'country'            => $request->country ?: 'Nigeria',
            'state'              => $request->state,
            'lga'                => $request->lga,
            'ward'               => $request->ward,
            'password'           => Hash::make($request->password),
            'application_status' => $needsApproval ? 'pending' : 'approved',
            'is_active'          => ! $needsApproval,
        ];

        if ($isEmail) {
            $userData['email'] = $identifier;
        } else {
            $userData['phone']             = $normalizedPhone;
            $userData['phone_verified_at'] = $needsApproval ? null : now();
        }

        $user = User::create($userData);

        // Redeem invite code if provided (farmers only)
        $inviteCode = null;
        if ($role === 'farmer' && $request->filled('invite_code')) {
            $inviteCode = InviteCode::where('code', strtoupper(trim($request->invite_code)))->first();
            if ($inviteCode && $inviteCode->isValid()) {
                $inviteCode->increment('used_count');
                $user->update(['is_pilot' => true]);
                // Override pendingPlan with the invite code's plan
                $request->merge(['plan' => $inviteCode->plan]);
            }
        }

        // Preserve plan selection through the verification flow
        $pendingPlan = $request->input('plan', '');
        $validPlans  = array_keys(config('subscription.plans', []));
        if (!in_array($pendingPlan, $validPlans)) {
            $pendingPlan = '';
        }

        // Store uploaded documents (base64 in DB — survives Render ephemeral wipes)
        if ($request->hasFile('documents')) {
            $docLabels = $this->getDocumentLabels($role);

            foreach ($request->file('documents') as $key => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $docType  = is_string($key) ? $key : 'document_' . ($key + 1);
                $label    = $docLabels[$docType] ?? ucwords(str_replace('_', ' ', $docType));

                UserDocument::create([
                    'user_id'         => $user->id,
                    'document_type'   => $docType,
                    'document_label'  => $label,
                    'original_name'   => $file->getClientOriginalName(),
                    'mime_type'       => $file->getMimeType(),
                    'file_size'       => $file->getSize(),
                    'content_base64'  => base64_encode(file_get_contents($file->getRealPath())),
                ]);
            }
        }

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

            if ($pendingPlan && $role === 'farmer') {
                return redirect()->route('subscription.plans')
                    ->with('success', 'Welcome to MSAS FarmAI! Start your free 14-day trial below.');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to MSAS FarmAI! Your account has been created.');
        }

        // Email farmer: OTP verification flow
        $plain       = $this->otp->generate($identifier, 'registration');
        $emailFailed = ! $this->otp->sendViaEmail($identifier, $plain, $user->first_name, $user->id, 'registration');
        $expiresAt   = $this->otp->expiresAt($identifier, 'registration');

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
