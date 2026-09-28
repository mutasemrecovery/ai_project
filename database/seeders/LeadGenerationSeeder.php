<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Icp;
use App\Models\LeadSource;
use App\Models\PromptTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class LeadGenerationSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Laravel Development',
            'Flutter Development',
            'Web Development',
            'Mobile Applications',
            'API Development',
            'Admin Dashboards',
            'AI Integration',
            'AI Automation',
            'WhatsApp Integration',
            'CRM',
            'ERP',
            'SaaS MVP Development',
            'Custom Software',
        ];

        $countries = ['Jordan', 'Saudi Arabia', 'UAE', 'Qatar', 'Kuwait', 'Bahrain', 'Oman'];

        $this->setting('company.name', 'Recovery', 'string', 'company');
        $this->setting('company.website', null, 'string', 'company');
        $this->setting('company.description', 'Software development company offering custom business software and AI integration.', 'string', 'company');
        $this->setting('company.services', $services, 'array', 'company');
        $this->setting('target.countries', $countries, 'array', 'targeting');
        $this->setting('minimum_lead_score', 40, 'integer', 'lead_generation');
        $this->setting('daily_discovery_limit', 100, 'integer', 'lead_generation');
        $this->setting('daily_outreach_limit', 25, 'integer', 'lead_generation');
        $this->setting('automatic_follow_up_enabled', false, 'boolean', 'outreach');
        $this->setting('timezone', 'Asia/Amman', 'string', 'general');

        LeadSource::firstOrCreate(
            ['name' => 'Manual Entry'],
            [
                'type' => 'manual',
                'provider' => 'internal',
                'description' => 'Manual lead entry by admins.',
                'enabled' => true,
            ]
        );

        LeadSource::firstOrCreate(
            ['name' => 'Configured Search API'],
            [
                'type' => 'search_api',
                'provider' => 'custom',
                'description' => 'Official search API connector. Configure endpoint/API key before enabling.',
                'configuration' => [
                    'endpoint' => null,
                    'query_parameter' => 'q',
                    'results_path' => 'items',
                ],
                'enabled' => false,
            ]
        );

        Campaign::firstOrCreate(
            ['name' => 'Jordan Restaurants'],
            [
                'description' => 'Restaurants in Jordan that may need ordering, WhatsApp, mobile app, or restaurant management systems.',
                'countries' => ['Jordan'],
                'cities' => ['Amman', 'Zarqa', 'Irbid'],
                'industries' => ['Restaurant'],
                'services' => ['Website', 'Online Ordering', 'Mobile App', 'WhatsApp Integration', 'Restaurant Management System'],
                'keywords' => ['restaurant online ordering', 'whatsapp ordering', 'restaurant booking', 'food delivery'],
                'negative_keywords' => ['closed', 'permanently closed'],
                'minimum_score' => 40,
                'enabled' => true,
            ]
        );

        Icp::firstOrCreate(
            ['name' => 'GCC Business Software Buyers'],
            [
                'industries' => ['Restaurant', 'Retail', 'Healthcare', 'Education', 'Real Estate', 'Logistics'],
                'countries' => $countries,
                'company_sizes' => ['small', 'medium', 'large'],
                'services' => $services,
                'minimum_score' => 40,
                'preferred_signals' => ['manual booking', 'online ordering', 'expansion', 'automation', 'new branch'],
                'excluded_signals' => ['do not contact', 'closed'],
                'enabled' => true,
            ]
        );

        PromptTemplate::firstOrCreate(
            ['name' => 'default_company_analysis'],
            [
                'operation' => 'company_analysis',
                'system_prompt' => 'Analyze a public business lead for fit with software development services. Do not invent facts.',
                'user_prompt' => 'Use the supplied lead JSON and return the required structured JSON.',
                'enabled' => true,
            ]
        );
    }

    private function setting(string $key, mixed $value, string $type, string $group): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }
}
