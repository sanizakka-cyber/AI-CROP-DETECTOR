<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scan_ref',
        'type',
        'image_path',
        'image_thumbnail',
        // Subject identification
        'subject_name',
        'scientific_name',
        'detected_part',
        'health_status',
        'severity_level',
        // Core diagnosis
        'disease_name',
        'confidence_score',
        'urgency_level',
        // Detailed findings
        'symptoms_identified',
        'cause',
        'environmental_factors',
        'nutrient_deficiencies',
        'pest_detection',
        // Treatment & prevention
        'first_aid_steps',
        'recommended_medication',
        'preventive_measures',
        'fertilizer_recommendation',
        'recovery_period',
        'best_practices',
        'vet_referral_advice',
        // Explainable AI
        'explanation',
        'status',
        'language',
    ];

    protected $casts = [
        'confidence_score' => 'float',
    ];

    /**
     * Standardized, concurrency-safe public Scan ID: MSAS-SCN-YYYYMMDD-000001.
     * Backed by a native Postgres sequence (diagnoses_scan_seq) so two
     * simultaneous scans can never collide — never derive this from count()+1.
     */
    public static function generateScanRef(): string
    {
        $seq = DB::select("SELECT nextval('diagnoses_scan_seq') AS n")[0]->n;

        return sprintf('MSAS-SCN-%s-%06d', now()->format('Ymd'), $seq);
    }

    /**
     * Canonical confidence display: real model score + honesty band, or an
     * explicit "unavailable" state — never a fabricated percentage.
     * Bands mirror the spec's own worked examples (92→High, 76→Good,
     * 67→Acceptable, 54→Low) with 65% as the automated-acceptance line.
     */
    public function getConfidenceLabelAttribute(): string
    {
        if ($this->confidence_score === null) {
            return 'N/A — AI Analysis Unavailable';
        }

        $pct = (int) round($this->confidence_score);
        $tier = match (true) {
            $pct >= 85 => 'High Confidence',
            $pct >= 70 => 'Good Confidence',
            $pct >= 65 => 'Acceptable / Review if necessary',
            default    => 'Low Confidence / Expert Review Required',
        };

        return "{$pct}% — {$tier}";
    }

    /** Plain "87%" (or null) — for spots that only need the number, one format everywhere. */
    public function getConfidencePercentAttribute(): ?string
    {
        return $this->confidence_score === null ? null : number_format($this->confidence_score, 0) . '%';
    }

    /**
     * One canonical status vocabulary for display, mapping the underlying
     * DB/API status values (which stay untouched to avoid breaking existing
     * filter logic) to the spec's four scan statuses.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'needs_review') {
            return 'Failed';
        }
        if ($this->status === 'pending') {
            return 'Pending Review';
        }
        if (in_array($this->status, ['reviewed', 'confirmed'], true)) {
            return ($this->confidence_score !== null && $this->confidence_score < 65)
                ? 'Low Confidence'
                : 'Completed';
        }

        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(\App\Models\DiagnosisFeedback::class);
    }

    public function myFeedback()
    {
        return $this->hasOne(\App\Models\DiagnosisFeedback::class)
            ->where('user_id', auth()->id());
    }

    public function narrations()
    {
        return $this->hasMany(DiagnosisNarration::class);
    }

    /**
     * Plain-text narration script for this diagnosis — the single source of
     * truth for both the browser speechSynthesis path and server-generated
     * TTS audio, so the two can never drift out of sync with each other.
     */
    public function narrationText(): string
    {
        $typeLbl  = match($this->type) { 'plant' => 'Crop / Plant', 'soil' => 'Soil Assessment', 'pest' => 'Pest Identification', default => 'Livestock' };
        $severity = $this->severity_level ?? '';
        $urgency  = $this->urgency_level ?? 'Medium';

        $lines = ["AI Diagnostic Report. {$typeLbl} scan."];
        if ($this->subject_name)                                                   $lines[] = "Subject: {$this->subject_name}.";
        if ($this->scientific_name && $this->scientific_name !== 'Unknown')        $lines[] = "Scientific name: {$this->scientific_name}.";
        if ($this->detected_part)                                                  $lines[] = "Detected part: {$this->detected_part}.";
        if ($this->health_status)                                                  $lines[] = "Health status: {$this->health_status}.";
        $lines[] = "Condition: {$this->disease_name}.";
        $lines[] = $this->confidence_score !== null
            ? "Confidence: " . (int) round($this->confidence_score) . " percent."
            : "AI analysis was unavailable for this scan. Pending expert review.";
        if ($severity)                                                             $lines[] = "Severity: {$severity}.";
        $lines[] = "Urgency: {$urgency}.";
        if ($this->symptoms_identified)                                            $lines[] = "Symptoms observed: {$this->symptoms_identified}.";
        if ($this->cause)                                                          $lines[] = "Root cause: {$this->cause}.";
        if ($this->environmental_factors)                                          $lines[] = "Environmental factors: {$this->environmental_factors}.";
        if ($this->nutrient_deficiencies && $this->nutrient_deficiencies !== 'None detected') $lines[] = "Nutrient deficiencies: {$this->nutrient_deficiencies}.";
        if ($this->pest_detection && $this->pest_detection !== 'No pest detected') $lines[] = "Pest detection: {$this->pest_detection}.";
        if ($this->first_aid_steps)                                                $lines[] = "Immediate action: {$this->first_aid_steps}.";
        if ($this->recommended_medication)                                         $lines[] = "Treatment: {$this->recommended_medication}.";
        if ($this->fertilizer_recommendation)                                      $lines[] = "Fertilizer: {$this->fertilizer_recommendation}.";
        if ($this->preventive_measures)                                            $lines[] = "Prevention: {$this->preventive_measures}.";
        if ($this->recovery_period)                                                $lines[] = "Estimated recovery: {$this->recovery_period}.";
        if ($this->vet_referral_advice)                                            $lines[] = "Expert advice: {$this->vet_referral_advice}.";
        $lines[] = "Always consult a certified specialist before applying any treatment.";

        return implode(' ', $lines);
    }
}
