<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('password');
            $table->string('country', 120)->nullable()->after('bio');
            $table->string('language', 60)->nullable()->after('country');
            $table->string('currency', 20)->nullable()->after('language');
            $table->string('avatar_url')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'country', 'language', 'currency', 'avatar_url']);
        });
    }
};
