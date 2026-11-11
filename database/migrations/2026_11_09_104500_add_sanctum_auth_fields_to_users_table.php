<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Add username, status, verification token, and ensure tokens tables exist for Sanctum
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add missing auth fields to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username', 190)->nullable()->unique()->after('id');
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->string('status', 20)->default('pending')->after('password');
                }
                if (!Schema::hasColumn('users', 'verification_token')) {
                    $table->string('verification_token', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('users', 'verification_token_expires_at')) {
                    $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token');
                }
                if (!Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                }
            });
        }

        // 2. Ensure personal_access_tokens table exists for Laravel Sanctum
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        // 3. Ensure password_reset_tokens table exists for Laravel password broker
        if (!Schema::hasTable('password_reset_tokens') && !Schema::hasTable('password_resets')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'verification_token_expires_at')) {
                    $table->dropColumn('verification_token_expires_at');
                }
                if (Schema::hasColumn('users', 'verification_token')) {
                    $table->dropColumn('verification_token');
                }
                if (Schema::hasColumn('users', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('users', 'username')) {
                    $table->dropColumn('username');
                }
            });
        }
    }
};

