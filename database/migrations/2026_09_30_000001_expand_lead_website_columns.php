<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('website')->nullable()->change();
        });

        Schema::table('raw_leads', function (Blueprint $table) {
            $table->text('website')->nullable()->change();
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->text('website')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('website')->nullable()->change();
        });

        Schema::table('raw_leads', function (Blueprint $table) {
            $table->string('website')->nullable()->change();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('website')->nullable()->change();
        });
    }
};
