<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('provider')->nullable();
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('countries')->nullable();
            $table->json('cities')->nullable();
            $table->json('industries')->nullable();
            $table->json('services')->nullable();
            $table->json('keywords')->nullable();
            $table->json('negative_keywords')->nullable();
            $table->unsignedTinyInteger('minimum_score')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('icps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('industries')->nullable();
            $table->json('countries')->nullable();
            $table->json('cities')->nullable();
            $table->json('company_sizes')->nullable();
            $table->json('services')->nullable();
            $table->unsignedTinyInteger('minimum_score')->default(0);
            $table->json('preferred_signals')->nullable();
            $table->json('excluded_signals')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('icp_id')->nullable()->constrained('icps')->nullOnDelete();
            $table->string('company_name');
            $table->string('normalized_company_name')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('website')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->string('industry')->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->string('source_reference')->nullable();
            $table->text('description')->nullable();
            $table->string('company_size')->nullable();
            $table->text('detected_need')->nullable();
            $table->json('detected_services')->nullable();
            $table->json('business_signals')->nullable();
            $table->text('ai_summary')->nullable();
            $table->longText('ai_reasoning')->nullable();
            $table->unsignedTinyInteger('lead_score')->default(0)->index();
            $table->unsignedTinyInteger('intent_score')->default(0);
            $table->unsignedTinyInteger('business_fit_score')->default(0);
            $table->unsignedTinyInteger('project_value_score')->default(0);
            $table->unsignedTinyInteger('digital_gap_score')->default(0);
            $table->string('priority')->default('low')->index();
            $table->string('status')->default('new')->index();
            $table->boolean('do_not_contact')->default(false)->index();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();
        });

        Schema::create('raw_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('source')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('location')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('new')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->json('public_links')->nullable();
            $table->json('technologies')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('linkedin_url')->nullable();
            $table->string('source')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('outreaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('channel')->default('email')->index();
            $table->string('message_type')->default('professional_email');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->json('personalization_evidence')->nullable();
            $table->string('status')->default('pending_approval')->index();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('outreach_id')->nullable()->constrained('outreaches')->nullOnDelete();
            $table->unsignedTinyInteger('sequence_number')->default(1);
            $table->unsignedInteger('delay_days')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('pending_approval')->index();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('operation')->index();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost', 12, 6)->default(0);
            $table->timestamps();
        });

        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('operation')->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->longText('user_prompt');
            $table->json('response_schema')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general')->index();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('outreaches');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('raw_leads');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('icps');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('lead_sources');
    }
};
