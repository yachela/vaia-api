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
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->enum('status', ['pending', 'generating', 'ready', 'failed'])
                ->default('pending')
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
