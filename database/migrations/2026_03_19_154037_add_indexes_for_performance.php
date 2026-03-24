<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar índices para optimizar consultas frecuentes.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index('trip_id');
        });

        Schema::table('packing_lists', function (Blueprint $table) {
            $table->index('trip_id');
        });

        Schema::table('packing_items', function (Blueprint $table) {
            $table->index('packing_list_id');
        });
    }

    /**
     * Reverse the migrations.
     * Eliminar índices creados.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['trip_id']);
        });

        Schema::table('packing_lists', function (Blueprint $table) {
            $table->dropIndex(['trip_id']);
        });

        Schema::table('packing_items', function (Blueprint $table) {
            $table->dropIndex(['packing_list_id']);
        });
    }
};
