<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_document_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trip_id')->constrained('trips')->onDelete('cascade')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_document_checklists');
    }
};
