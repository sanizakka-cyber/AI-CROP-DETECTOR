<x-guest-layout>

<div x-data="{
    step: 1,
    role: '{{ old('role', '') }}',
    roleName: '',
    roles: {
        farmer:            { label:'Farmer / Livestock Owner', desc:'Manage your farm, animals & crops',     icon:'🌾' },
        vet:               { label:'Veterinarian',             desc:'Diagnose animals & write prescriptions',icon:'💉' },
        agronomist:        { label:'Agronomist',               desc:'Crop advisory & soil analysis',          icon:'🌿' },
        'agro-dealer':     { label:'Agro-Dealer',              desc:'Sell inputs, seeds & equipment',         icon:'🏪' },
        'extension-officer':{ label:'Extension Officer',       desc:'Train & support farmers in the field',   icon:'🧑‍🏫' },
        'field-officer':   { label:'Field Officer',            desc:'Ground-level monitoring & reporting',    icon:'🗺️' },
        finance:           { label:'Finance Officer',          desc:'Budgets, payments & financial records',  icon:'💰' },
        operations:        { label:'Operations Manager',       desc:'Logistics, supply chain & processes',    icon:'⚙️' },
        'customer-support':{ label:'Customer Support',         desc:'Help clients & resolve issues',          icon:'🎧' },
        'data-analyst':    { label:'Data Analyst',             desc:'Insights, reports & dashboards',         icon:'📊' }
    },
    selectRole(key) {
        this.role = key;
        this.roleName = this.roles[key].label;
        this.step = 2;
        window.scrollTo(0,0);
    }
}"
x-init="if(role && roles[role]){ roleName = roles[role].label; step = 2; }">

    {{-- ═══ STEP 1 — Role selection ═══ --}}
    <div x-show="step === 1">
        <div style="margin-bottom:18px;">
            <h2 style="font-family:'Poppins',sans-serif;font-size:19px;font-weight:800;color:#0f172a;margin:0 0 4px 0;">I am joining as…</h2>
            <p style="font-size:12px;color:#64748b;margin:0;">Select your role to get the right dashboard and tools.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:18px;">
            <template x-for="(cfg, key) in roles" :key="key">
                <button type="button"
                    @click="selectRole(key)"
                    style="display:flex;align-items:flex-start;gap:9px;padding:11px;border-radius:11px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;text-align:left;transition:all .2s;"
                    onmouseover="this.style.borderColor='#0F6B3E';this.style.background='#f0fdf4'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                    <span style="font-size:20px;line-height:1;flex-shrink:0;margin-top:1px;" x-text="cfg.icon"></span>
                    <span>
                        <span style="display:block;font-size:12px;font-weight:700;color:#1e293b;line-height:1.3;" x-text="cfg.label"></span>
                        <span style="display:block;font-size:10px;color:#94a3b8;margin-top:2px;line-height:1.4;" x-text="cfg.desc"></span>
                    </span>
                </button>
            </template>
        </div>

        <div style="text-align:center;padding-top:14px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">Already have an account?</span>
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">Sign In</a>
        </div>
    </div>

    {{-- ═══ STEP 2 — Registration form ═══ --}}
    <div x-show="step === 2" x-cloak>

        {{-- Validation errors --}}
        @if ($errors->any())
        <div class="fade-up-1" style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;margin-bottom:14px;">
            <svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span style="color:#dc2626;font-size:13px;font-weight:600;">{{ $errors->first() }}</span>
        </div>
        @endif

        {{-- Selected role badge + back --}}
        <div class="fade-up-1" style="display:flex;align-items:center;gap:8px;margin-bottom:18px;">
            <button type="button" @click="step=1"
                style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#64748b;background:#f1f5f9;border:none;border-radius:7px;padding:5px 9px;cursor:pointer;transition:all .18s;"
                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
                Change
            </button>
            <div style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;">
                <span style="font-size:15px;line-height:1;" x-text="roles[role] ? roles[role].icon : ''"></span>
                <span style="font-size:12px;font-weight:700;color:#0F6B3E;" x-text="roleName"></span>
            </div>
        </div>

        <div class="fade-up-2" style="margin-bottom:16px;">
            <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 2px 0;">Create your account</h2>
            <p style="font-size:12px;color:#64748b;margin:0;">Join MSAS Livestock & Agro Services today.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="role" :value="role">

            {{-- First & Last Name --}}
            <div class="fade-up-2" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label for="first_name" class="field-label">First Name *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <input id="first_name" class="form-input {{ $errors->has('first_name') ? 'error' : '' }}" style="padding-left:34px;"
                            type="text" name="first_name" value="{{ old('first_name') }}" required
                            autocomplete="off" placeholder="" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                    </div>
                    @error('first_name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="last_name" class="field-label">Last Name *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <input id="last_name" class="form-input {{ $errors->has('last_name') ? 'error' : '' }}" style="padding-left:34px;"
                            type="text" name="last_name" value="{{ old('last_name') }}" required
                            autocomplete="off" placeholder="" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                    </div>
                    @error('last_name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Middle Name --}}
            <div class="fade-up-3" style="margin-bottom:10px;">
                <label for="middle_name" class="field-label">Middle Name <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">(optional)</span></label>
                <input id="middle_name" class="form-input" type="text" name="middle_name" value="{{ old('middle_name') }}"
                    autocomplete="off" placeholder="" readonly
                    onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
            </div>

            {{-- Email --}}
            <div class="fade-up-3" style="margin-bottom:10px;">
                <label for="email" class="field-label">Email Address *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <input id="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}" style="padding-left:34px;"
                        type="email" name="email" value="{{ old('email') }}" required
                        autocomplete="off" placeholder="" readonly
                        onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                </div>
                @error('email')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Phone --}}
            <div class="fade-up-3" style="margin-bottom:10px;">
                <label for="phone" class="field-label">Phone Number *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <input id="phone" class="form-input {{ $errors->has('phone') ? 'error' : '' }}" style="padding-left:34px;"
                        type="tel" name="phone" value="{{ old('phone') }}" required
                        autocomplete="off" placeholder="08xxxxxxxxx" readonly
                        onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                </div>
                @error('phone')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Password + Confirm --}}
            <div class="fade-up-4" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div>
                    <label for="password" class="field-label">Password *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </div>
                        <input id="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}" style="padding-left:34px;"
                            type="password" name="password" required
                            autocomplete="new-password" placeholder="" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                    </div>
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirm *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <input id="password_confirmation" class="form-input {{ $errors->has('password_confirmation') ? 'error' : '' }}" style="padding-left:34px;"
                            type="password" name="password_confirmation" required
                            autocomplete="new-password" placeholder="" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                    </div>
                </div>
            </div>

            {{-- Terms --}}
            <div class="fade-up-4" style="display:flex;align-items:flex-start;gap:10px;margin-bottom:16px;padding:10px 12px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;">
                <input type="checkbox" id="terms" required
                    style="width:16px;height:16px;border-radius:4px;accent-color:#0F6B3E;cursor:pointer;margin-top:1px;flex-shrink:0;">
                <label for="terms" style="font-size:12px;color:#475569;cursor:pointer;line-height:1.5;">
                    I agree to the <a href="#" style="color:#0F6B3E;font-weight:700;text-decoration:none;">Terms of Service</a> and <a href="#" style="color:#0F6B3E;font-weight:700;text-decoration:none;">Privacy Policy</a>
                </label>
            </div>

            {{-- Submit --}}
            <div class="fade-up-5" style="margin-bottom:14px;">
                <button type="submit" class="btn-primary">
                    <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Create My Account
                    </span>
                </button>
            </div>

            {{-- Sign in link --}}
            <div class="fade-up-5" style="text-align:center;padding-top:12px;border-top:1px solid #f1f5f9;">
                <span style="font-size:13px;color:#94a3b8;">Already have an account?</span>
                <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">Sign In</a>
            </div>
        </form>
    </div>

</div>
</x-guest-layout>
