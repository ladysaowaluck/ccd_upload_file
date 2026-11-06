<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Lady : Creating 'cache' or 'cache_locks' tables violates the database constraint. Cache is configured to use 'file' storage.
    // Gemini : Focus strictly on existing tables from ccd_system_rds.sql and sn_user_auth_rds.sql without creating extra tables.
    public function up(): void
    {
        /*
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        */
    }
};
