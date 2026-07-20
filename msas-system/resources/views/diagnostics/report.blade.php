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
    <span>📄 MSAS FarmAI — Diagnostic Report #{{ $diagnosis->id }}</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <button class="btn-print" style="background:#334155" onclick="window.close()">✕ Close</button>
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
            {{ match($diagnosis->type) { 'plant'=>'🌿', 'soil'=>'🌱', default=>'🐄' } }}
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
                    <span class="meta-pill">📍 {{ $diagnosis->detected_part }}</span>
                    @endif
                    @if($diagnosis->recovery_period)
                    <span class="meta-pill">⏱ Recovery: {{ $diagnosis->recovery_period }}</span>
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
                    <span>⚠️</span>
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
                <div class="section-label">🔬 Symptoms Identified</div>
                <p>{{ $diagnosis->symptoms_identified }}</p>
            </div>
            @endif
            @if($diagnosis->cause)
            <div class="section-card s-slate">
                <div class="section-label">🔍 Root Cause</div>
                <p>{{ $diagnosis->cause }}</p>
            </div>
            @endif
            @if($diagnosis->environmental_factors)
            <div class="section-card s-sky">
                <div class="section-label">🌡️ Environmental Factors</div>
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
                <div class="section-label">🧪 Nutrient Deficiency</div>
                <p>{{ $diagnosis->nutrient_deficiencies }}</p>
            </div>
            @endif
            @if($showPests)
            <div class="section-card s-orange">
                <div class="section-label">🐛 Pest Detection</div>
                <p>{{ $diagnosis->pest_detection }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Immediate Action --}}
        @if($diagnosis->first_aid_steps)
        <div class="action-block">
            <div class="section-label">🚑 Immediate Action Required</div>
            <p>{{ $diagnosis->first_aid_steps }}</p>
        </div>
        @endif

        {{-- Treatment + Fertilizer --}}
        @php $row3 = collect(['recommended_medication','fertilizer_recommendation'])->filter(fn($f)=>!empty($diagnosis->$f)); @endphp
        @if($row3->isNotEmpty())
        <div class="section-grid">
            @if($diagnosis->recommended_medication)
            <div class="section-card s-emerald">
                <div class="section-label">💊 Recommended Treatment</div>
                <p>{{ $diagnosis->recommended_medication }}</p>
            </div>
            @endif
            @if($diagnosis->fertilizer_recommendation)
            <div class="section-card s-teal">
                <div class="section-label">🌾 Fertilizer Recommendation</div>
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
                <div class="section-label">🛡️ Prevention Measures</div>
                <p>{{ $diagnosis->preventive_measures }}</p>
            </div>
            @endif
            @if($diagnosis->best_practices)
            <div class="section-card s-indigo">
                <div class="section-label">📚 Best Practices</div>
                <p>{{ $diagnosis->best_practices }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Expert Advice --}}
        @if($diagnosis->vet_referral_advice)
        <div class="section-card s-amber" style="display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:18px;flex-shrink:0">👨‍⚕️</span>
            <div>
                <div class="section-label">Expert Recommendation</div>
                <p>{{ $diagnosis->vet_referral_advice }}</p>
            </div>
        </div>
        @endif

        {{-- Explainable AI --}}
        @if($diagnosis->explanation)
        <div class="explanation-box">
            <div class="section-label">🧠 Why this diagnosis? (Explainable AI)</div>
            <p>{{ $diagnosis->explanation }}</p>
        </div>
        @endif

    </div>

    {{-- ── Disclaimer ──────────────────────────────────────────────────── --}}
    <div class="disclaimer-box">
        ⚠️ <strong>Disclaimer:</strong> This report is generated by the MSAS FarmAI automated diagnostic system using computer vision and large language model analysis. It is intended as a decision-support tool only and should not replace professional agricultural or veterinary advice. Always consult a certified
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
