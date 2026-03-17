<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packing_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('packing_list_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('category', ['Higiene', 'Ropa', 'Tecnología', 'Documentación']);
            $table->boolean('is_packed')->default(false);
            $table->boolean('is_suggested')->default(false);
            $table->text('suggestion_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_items');
    }
};
