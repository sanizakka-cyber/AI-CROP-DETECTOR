<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Proof of agreement to the Terms, Privacy Policy and Refund Policy, and the
 * age each user declared at registration.
 *
 * - users.* columns hold the latest accepted versions, for quick checks.
 * - legal_acceptances keeps every acceptance as an audit trail (who, which
 *   document and version, when, from where, and for which payment).
 *
 * Only additive: nothing existing is changed or removed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'terms_version')) {
                $table->string('terms_version', 32)->nullable();
            }
            if (! Schema::hasColumn('users', 'privacy_version')) {
                $table->string('privacy_version', 32)->nullable();
            }
            if (! Schema::hasColumn('users', 'age_declared')) {
                $table->unsignedTinyInteger('age_declared')->nullable();
            }
            if (! Schema::hasColumn('users', 'age_declared_at')) {
                $table->timestamp('age_declared_at')->nullable();
            }
        });

        if (! Schema::hasTable('legal_acceptances')) {
            Schema::create('legal_acceptances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('document', 20);           // terms | privacy | refund | age
                $table->string('version', 32);
                $table->string('context', 40);            // registration_web | registration_app | reacceptance_web | subscription_web | subscription_app
                $table->string('reference', 100)->nullable(); // payment reference for refund acknowledgements
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('app_version', 40)->nullable();
                $table->timestamp('accepted_at');
                $table->timestamps();

                $table->index(['user_id', 'document']);
                $table->index('reference');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_acceptances');

        Schema::table('users', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['terms_accepted_at', 'terms_version', 'privacy_version', 'age_declared', 'age_declared_at'],
                fn ($c) => Schema::hasColumn('users', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
