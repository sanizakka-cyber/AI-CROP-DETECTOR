<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        $data = [
            'profile' => $user->only([
                'first_name','middle_name','last_name','email','phone',
                'role','state','lga','ward','country','created_at',
            ]),
            'diagnoses' => Diagnosis::where('user_id', $user->id)
                ->select('type','subject_name','disease_name','confidence_score','created_at')
                ->get()->toArray(),
            'payments' => Payment::where('user_id', $user->id)
                ->select('reference','amount','status','module','created_at')
                ->get()->toArray(),
            'exported_at' => now()->toISOString(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($user->email) {
            try {
                Mail::raw(
                    "Your MSAS FarmAI data export is attached.\n\nThis export was generated in compliance with the Nigeria Data Protection Regulation (NDPR).\n\nIf you did not request this, please contact support immediately.",
                    function ($m) use ($user, $json) {
                        $m->to($user->email)
                          ->subject('Your MSAS FarmAI Data Export')
                          ->attachData($json, 'msas_data_export.json', ['mime'=>'application/json']);
                    }
                );
                $user->update(['data_export_completed_at' => now()]);
                AuditLog::record('compliance.data_export_sent', 'User', $user->id);
            } catch (\Throwable $e) {
                Log::error('ExportUserDataJob email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }
    }
}