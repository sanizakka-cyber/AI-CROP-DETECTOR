<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MSAS FarmAI Report #{{ $diagnosis->id }}</title>
<style>
/* ── Base ──────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
    color: #1e293b;
    background: #f8fafc;
    padding: 0;
}
a { color: inherit; text-decoration: none; }

/* ── Print controls (web-only) ─────────────────────────────────────── */
.print-bar {
    background: #1e293b;
    color: #fff;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 100;
}
.print-bar span { font-size: 12px; opacity: .8; }
.btn-print {
    background: #10b981;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-print:hover { background: #059669; }

/* ── Page container ─────────────────────────────────────────────────── */
.page {
    max-width: 794px;          /* A4 width */
    margin: 24px auto;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,.12);
}

/* ── Header ─────────────────────────────────────────────────────────── */
.rpt-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f4c2a 100%);
    color: #fff;
    padding: 24px 28px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.rpt-logo { font-size: 28px; font-weight: 900; letter-spacing: -1px; }
.rpt-logo span { color: #34d399; }
.rpt-meta { text-align: right; font-size: 10px; opacity: .8; line-height: 1.7; }
.rpt-id { font-size: 11px; background: rgba(255,255,255,.15); border-radius: 6px; padding: 4px 10px; margin-top: 6px; display: inline-block; }

/* ── Subject banner ─────────────────────────────────────────────────── */
.subject-banner {
    padding: 16px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.subject-icon { font-size: 36px; }
.subject-name { font-size: 22px; font-weight: 900; color: #0f172a; }
.subject-sci  { font-style: italic; color: #64748b; font-size: 12px; margin-top: 2px; }
.subject-right { margin-left: auto; text-align: right; }
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin: 2px;
}
.badge-red    { background: #fee2e2; color: #991b1b; }
.badge-orange { background: #ffedd5; color: #9a3412; }
.badge-amber  { background: #fef3c7; color: #92400e; }
.badge-yellow { background: #fefce8; color: #854d0e; }
.badge-green  { background: #d1fae5; color: #065f46; }
.badge-blue   { background: #dbeafe; color: #1e40af; }

/* ── Scan image + diagnosis headline ───────────────────────────────── */
.scan-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 0;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.scan-img-wrap { position: relative; background: #f1f5f9; }
.scan-img-wrap img { width: 200px; height: 200px; object-fit: cover; display: block; }
.conf-pill {
    position: absolute;
    bottom: 8px; left: 8px;
    background: rgba(0,0,0,.75);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
}
.diag-headline {
    padding: 18px 22px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
}
.diag-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #94a3b8; }
.diag-name  { font-size: 19px; font-weight: 900; color: #0f172a; line-height: 1.2; }
.meta-row   { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.meta-pill  { background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 6px; }

@media (max-width: 600px) {
    .scan-row { grid-template-columns: 1fr; }
    .scan-img-wrap img { width: 100%; height: 200px; }
}

/* ── Confidence bar ─────────────────────────────────────────────────── */
.conf-bar-wrap { padding: 0 22px 16px; }
.conf-bar-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; display: flex; justify-content: space-between; }
.conf-bar-bg { background: #e2e8f0; border-radius: 999px; height: 8px; overflow: hidden; }
.conf-bar-fill {
    height: 8px; border-radius: 999px;
    background: {{ $diagnosis->confidence_score >= 80 ? '#10b981' : ($diagnosis->confidence_score >= 60 ? '#f59e0b' : '#ef4444') }};
    width: {{ min((float)$diagnosis->confidence_score, 100) }}%;
}

/* ── Section grid ───────────────────────────────────────────────────── */
.sections { padding: 16px 22px; display: flex; flex-direction: column; gap: 12px; }
.section-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }

.section-card {
    border-radius: 10px;
    padding: 12px 14px;
    border-width: 1px;
    border-style: solid;
}
.s-red    { background: #fef2f2; border-color: #fecaca; }
.s-slate  { background: #f8fafc; border-color: #e2e8f0; }
.s-sky    { background: #f0f9ff; border-color: #bae6fd; }
.s-lime   { background: #f7fee7; border-color: #d9f99d; }
.s-orange { background: #fff7ed; border-color: #fed7aa; }
.s-blue   { background: #eff6ff; border-color: #bfdbfe; }
.s-emerald{ background: #ecfdf5; border-color: #a7f3d0; }
.s-teal   { background: #f0fdfa; border-color: #99f6e4; }
.s-violet { background: #f5f3ff; border-color: #ddd6fe; }
.s-amber  { background: #fffbeb; border-color: #fde68a; }
.s-indigo { background: #eef2ff; border-color: #c7d2fe; }

.section-label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.s-red .section-label    { color: #b91c1c; }
.s-slate .section-label  { color: #475569; }
.s-sky .section-label    { color: #0284c7; }
.s-lime .section-label   { color: #4d7c0f; }
.s-orange .section-label { color: #c2410c; }
.s-blue .section-label   { color: #1d4ed8; }
.s-emerald .section-label{ color: #047857; }
.s-teal .section-label   { color: #0f766e; }
.s-violet .section-label { color: #6d28d9; }
.s-amber .section-label  { color: #b45309; }
.s-indigo .section-label { color: #4338ca; }

.section-card p { font-size: 11px; line-height: 1.55; color: #1e293b; }

/* ── Action block ───────────────────────────────────────────────────── */
.action-block {
    background: #1d4ed8;
    color: #fff;
    border-radius: 10px;
    padding: 14px 16px;
}
.action-block .section-label { color: rgba(255,255,255,.7); }
.action-block p { color: #fff; font-size: 11px; line-height: 1.6; white-space: pre-line; }

/* ── Explanation ────────────────────────────────────────────────────── */
.explanation-box {
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    border-radius: 10px;
    padding: 12px 14px;
}
.explanation-box .section-label { color: #7c3aed; font-size: 9px; }
.explanation-box p { font-size: 11px; color: #4c1d95; line-height: 1.6; }

/* ── Low-confidence warning ─────────────────────────────────────────── */
.low-conf-box {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}
.low-conf-box p { font-size: 11px; color: #92400e; }

/* ── Footer ─────────────────────────────────────────────────────────── */
.rpt-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 14px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 9px;
    color: #94a3b8;
}
.rpt-footer strong { color: #475569; }
.disclaimer-box {
    padding: 10px 28px 20px;
    font-size: 9px;
    color: #94a3b8;
    line-height: 1.6;
    border-top: 1px dashed #e2e8f0;
}

/* ── Print media ────────────────────────────────────────────────────── */
@media print {
    body { background: #fff; padding: 0; font-size: 11px; }
    .print-bar { display: none !important; }
    .page { max-width: 100%; margin: 0; border-radius: 0; box-shadow: none; }
    @page { size: A4; margin: 12mm 14mm; }
    .section-card, .action-block { page-break-inside: avoid; }
}
</style>
</head>
<body>

{{-- ── Print controls bar (hidden on print) ── --}}
<div class="print-bar">
    <span style="display:flex;align-items:center;gap:6px"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="opacity:.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> MSAS FarmAI — Diagnostic Report #{{ $diagnosis->id }}</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-print" onclick="window.print()" style="display:flex;align-items:center;gap:6px"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Print / Save as PDF</button>
        <button class="btn-print" style="background:#334155" onclick="window.close()">Close</button>
    </div>
</div>

<div class="page">

    {{-- ── Report Header ──────────────────────────────────────────────── --}}
    <div class="rpt-header">
        <div>
            <div class="rpt-logo">MSAS <span>FarmAI</span></div>
            <div style="font-size:11px;opacity:.7;margin-top:4px">Intelligent Agricultural Diagnostic System</div>
            <div class="rpt-id">Report ID: MSAS-{{ str_pad($diagnosis->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="rpt-meta">
            <div><strong>Farmer:</strong> {{ $user->name }}</div>
            <div><strong>Email:</strong> {{ $user->email }}</div>
            <div><strong>Scan Type:</strong>
                {{ match($diagnosis->type) { 'plant'=>'Crop / Plant', 'soil'=>'Soil Assessment', default=>'Livestock' } }}
            </div>
            <div><strong>Date:</strong> {{ $diagnosis->created_at->format('F j, Y  g:i A') }}</div>
            <div><strong>Status:</strong> {{ ucfirst($diagnosis->status) }}</div>
        </div>
    </div>

    {{-- ── Subject Banner ─────────────────────────────────────────────── --}}
    @if($diagnosis->subject_name)
    <div class="subject-banner" style="background:{{ $diagnosis->health_status === 'Healthy' ? '#ecfdf5' : '#fff7ed' }}; border-bottom:1px solid #e2e8f0">
        <div class="subject-icon">
            @if($diagnosis->type === 'plant')
            <svg width="36" height="36" fill="none" stroke="#15803d" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 4 2 5c0 5 4 8 10 7zm0 0c0-5 5-8 10-7-1 5-5 8-10 7"/></svg>
            @elseif($diagnosis->type === 'soil')
            <svg width="36" height="36" fill="none" stroke="#92400e" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22v-5m0 0c-2-1-5-1.5-7 0m7 0c2-1 5-1.5 7 0M5 21h14M12 17V8m0-5v2M9 5.5C9 4 10 3 12 3s3 1 3 2.5"/></svg>
            @else
            <svg width="36" height="36" fill="none" stroke="#b45309" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5S3 9 3 7.5A2.5 2.5 0 018 7.5H9M4.5 10.5H19.5M19.5 10.5S21 9 21 7.5A2.5 2.5 0 0016 7.5H15M19.5 10.5V16M4.5 10.5V16M7 16v3m10-3v3M7 16h10"/></svg>
            @endif
        </div>
        <div>
            <div class="subject-name">{{ $diagnosis->subject_name }}</div>
            @if($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown')
            <div class="subject-sci">{{ $diagnosis->scientific_name }}</div>
            @endif
        </div>
        <div class="subject-right">
            @if($diagnosis->severity_level)
            <span class="badge badge-{{ match($diagnosis->severity_level) { 'Critical','Severe'=>'red', 'Moderate'=>'orange', 'Mild'=>'amber', default=>'green' } }}">
                {{ $diagnosis->severity_level }} Severity
            </span>
            @endif
            @if($diagnosis->urgency_level)
            <span class="badge badge-{{ match($diagnosis->urgency_level) { 'Emergency','High'=>'red', 'Medium'=>'amber', default=>'green' } }}">
                {{ $diagnosis->urgency_level }} Urgency
            </span>
            @endif
            @if($diagnosis->health_status)
            <span class="badge badge-{{ $diagnosis->health_status === 'Healthy' ? 'green' : 'orange' }}">
                {{ $diagnosis->health_status }}
            </span>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Scan Image + Diagnosis Headline ────────────────────────────── --}}
    <div class="scan-row">
        <div class="scan-img-wrap">
            <img id="rpt-scan-img"
                 src="{{ $imageB64 ?? Storage::disk('public')->url($diagnosis->image_path) }}"
                 alt="Scanned Image"
                 onerror="this.onerror=null;this.style.opacity='0.3';">
            <div class="conf-pill">AI Confidence: {{ number_format($diagnosis->confidence_score, 0) }}%</div>
        </div>
        <div>
            <div class="diag-headline">
                <div class="diag-label">Diagnosis Result</div>
                <div class="diag-name">{{ $diagnosis->disease_name }}</div>
                <div class="meta-row">
                    @if($diagnosis->detected_part)
                    <span class="meta-pill">Part: {{ $diagnosis->detected_part }}</span>
                    @endif
                    @if($diagnosis->recovery_period)
                    <span class="meta-pill">Recovery: {{ $diagnosis->recovery_period }}</span>
                    @endif
                </div>
            </div>

            {{-- Confidence bar --}}
            <div class="conf-bar-wrap">
                <div class="conf-bar-label">
                    <span>AI Confidence Score</span>
                    <span>{{ number_format($diagnosis->confidence_score, 1) }}%</span>
                </div>
                <div class="conf-bar-bg"><div class="conf-bar-fill"></div></div>
            </div>

            @if($diagnosis->confidence_score < 60)
            <div style="padding: 0 22px 12px">
                <div class="low-conf-box">
                    <svg width="16" height="16" fill="none" stroke="#92400e" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <p>Confidence is limited. Please capture a clearer image or consult an expert for a definitive diagnosis.</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Detailed Findings ───────────────────────────────────────────── --}}
    <div class="sections">

        {{-- Symptoms + Cause + Environment --}}
        @php $row1 = collect(['symptoms_identified','cause','environmental_factors'])->filter(fn($f)=>!empty($diagnosis->$f)); @endphp
        @if($row1->isNotEmpty())
        <div class="section-grid">
            @if($diagnosis->symptoms_identified)
            <div class="section-card s-red">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Symptoms Identified</div>
                <p>{{ $diagnosis->symptoms_identified }}</p>
            </div>
            @endif
            @if($diagnosis->cause)
            <div class="section-card s-slate">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Root Cause</div>
                <p>{{ $diagnosis->cause }}</p>
            </div>
            @endif
            @if($diagnosis->environmental_factors)
            <div class="section-card s-sky">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20a3 3 0 006 0 3 3 0 00-3-2.69V6a3 3 0 00-6 0v11.31A3 3 0 009 20z"/></svg> Environmental Factors</div>
                <p>{{ $diagnosis->environmental_factors }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Nutrients + Pests --}}
        @php
            $showNutrients = $diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected';
            $showPests     = $diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected';
        @endphp
        @if($showNutrients || $showPests)
        <div class="section-grid">
            @if($showNutrients)
            <div class="section-card s-lime">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg> Nutrient Deficiency</div>
                <p>{{ $diagnosis->nutrient_deficiencies }}</p>
            </div>
            @endif
            @if($showPests)
            <div class="section-card s-orange">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/></svg> Pest Detection</div>
                <p>{{ $diagnosis->pest_detection }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Immediate Action --}}
        @if($diagnosis->first_aid_steps)
        <div class="action-block">
            <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> Immediate Action Required</div>
            <p>{{ $diagnosis->first_aid_steps }}</p>
        </div>
        @endif

        {{-- Treatment + Fertilizer --}}
        @php $row3 = collect(['recommended_medication','fertilizer_recommendation'])->filter(fn($f)=>!empty($diagnosis->$f)); @endphp
        @if($row3->isNotEmpty())
        <div class="section-grid">
            @if($diagnosis->recommended_medication)
            <div class="section-card s-emerald">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg> Recommended Treatment</div>
                <p>{{ $diagnosis->recommended_medication }}</p>
            </div>
            @endif
            @if($diagnosis->fertilizer_recommendation)
            <div class="section-card s-teal">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 4 2 5c0 5 4 8 10 7zm0 0c0-5 5-8 10-7-1 5-5 8-10 7"/></svg> Fertilizer Recommendation</div>
                <p>{{ $diagnosis->fertilizer_recommendation }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Prevention + Best Practices --}}
        @php $row4 = collect(['preventive_measures','best_practices'])->filter(fn($f)=>!empty($diagnosis->$f)); @endphp
        @if($row4->isNotEmpty())
        <div class="section-grid">
            @if($diagnosis->preventive_measures)
            <div class="section-card s-violet">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> Prevention Measures</div>
                <p>{{ $diagnosis->preventive_measures }}</p>
            </div>
            @endif
            @if($diagnosis->best_practices)
            <div class="section-card s-indigo">
                <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg> Best Practices</div>
                <p>{{ $diagnosis->best_practices }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Expert Advice --}}
        @if($diagnosis->vet_referral_advice)
        <div class="section-card s-amber" style="display:flex;gap:10px;align-items:flex-start">
            <svg width="20" height="20" fill="none" stroke="#b45309" stroke-width="1.8" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <div>
                <div class="section-label">Expert Recommendation</div>
                <p>{{ $diagnosis->vet_referral_advice }}</p>
            </div>
        </div>
        @endif

        {{-- Explainable AI --}}
        @if($diagnosis->explanation)
        <div class="explanation-box">
            <div class="section-label"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg> Why this diagnosis? (Explainable AI)</div>
            <p>{{ $diagnosis->explanation }}</p>
        </div>
        @endif

    </div>

    {{-- ── Disclaimer ──────────────────────────────────────────────────── --}}
    <div class="disclaimer-box">
        <svg width="12" height="12" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> <strong>Disclaimer:</strong> This report is generated by the MSAS FarmAI automated diagnostic system using computer vision and large language model analysis. It is intended as a decision-support tool only and should not replace professional agricultural or veterinary advice. Always consult a certified
        {{ $diagnosis->type === 'soil' ? 'Agronomist or Soil Scientist' : ($diagnosis->type === 'animal' ? 'Veterinary Doctor' : 'Agronomist or Extension Officer') }}
        before applying any treatment or making farm management decisions.
    </div>

    {{-- ── Report Footer ──────────────────────────────────────────────── --}}
    <div class="rpt-footer">
        <div>
            <strong>MSAS FarmAI</strong> — Intelligent Agricultural Diagnostics |
            Powered by Claude AI Vision
        </div>
        <div>
            Report #MSAS-{{ str_pad($diagnosis->id, 6, '0', STR_PAD_LEFT) }} ·
            Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>

</div>

<script>
window.addEventListener('load', function() {
    var img = document.getElementById('rpt-scan-img');

    function doPrint() {
        if (window.opener || window.history.length <= 1) {
            window.print();
        }
    }

    if (img && !img.complete) {
        // Wait for image to load before triggering print — avoids blank image in PDF
        img.addEventListener('load',  function() { setTimeout(doPrint, 400); });
        img.addEventListener('error', function() { setTimeout(doPrint, 400); });
        // Absolute fallback in case neither event fires (e.g., data URI already decoded)
        setTimeout(doPrint, 3000);
    } else {
        setTimeout(doPrint, 600);
    }
});
</script>

</body>
</html>
