<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('contact_methods')->nullable()->after('phone');
        });

        Schema::table('raw_leads', function (Blueprint $table) {
            $table->json('contact_methods')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('raw_leads', function (Blueprint $table) {
            $table->dropColumn('contact_methods');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('contact_methods');
        });
    }
};
