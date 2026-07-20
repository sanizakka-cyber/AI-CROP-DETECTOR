<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_documents')) return;

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_type');   // e.g. 'veterinary_license'
            $table->string('document_label');  // human-readable label
            $table->string('original_name');   // original filename
            $table->string('mime_type');
            $table->unsignedInteger('file_size')->default(0); // bytes
            $table->text('content_base64');    // base64-encoded file (persists across deploys)
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};
