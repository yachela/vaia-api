<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trip_document_checklist_id')->constrained('trip_document_checklists')->onDelete('cascade');
            $table->string('name', 255);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
