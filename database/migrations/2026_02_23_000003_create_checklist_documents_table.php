<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('checklist_item_id')->constrained('checklist_items')->onDelete('cascade')->unique();
            $table->string('file_name', 255);
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->enum('source', ['local', 'google_drive'])->default('local');
            $table->string('google_drive_file_id')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_documents');
    }
};
