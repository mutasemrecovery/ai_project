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

        $this->socialSearchSource(
            'LinkedIn Public Search',
            'linkedin',
            'Searches public LinkedIn company pages through your configured search API. Use only APIs and data access that your account is allowed to use.',
            ['site:linkedin.com/company', 'site:linkedin.com/showcase'],
            '-jobs -careers -login',
            ['| LinkedIn', '- LinkedIn', ' LinkedIn'],
            ['linkedin.com/company/', 'linkedin.com/showcase/'],
            ['linkedin.com/jobs/', 'linkedin.com/posts/', 'linkedin.com/pulse/', 'linkedin.com/feed/']
        );

        $this->socialSearchSource(
            'Facebook Public Search',
            'facebook',
            'Searches public Facebook business pages through your configured search API. Use only APIs and data access that your account is allowed to use.',
            ['site:facebook.com'],
            '-groups -posts -photos -videos -login',
            ['| Facebook', '- Facebook', ' Facebook'],
            ['facebook.com/'],
            ['/groups/', '/posts/', '/photos/', '/videos/', '/events/', '/login', 'sharer.php']
        );

        $this->socialSearchSource(
            'X Twitter Public Search',
            'twitter',
            'Searches public X/Twitter profiles through your configured search API. Use only APIs and data access that your account is allowed to use.',
            ['site:x.com', 'site:twitter.com'],
            '-status -statuses -login',
            ['| X', '- X', '/ X', '| Twitter', '- Twitter', '/ Twitter', ' Twitter'],
            ['x.com/', 'twitter.com/'],
            ['/status/', '/statuses/', '/i/flow/login', '/search']
        );

        $this->campaigns($countries);

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

    private function socialSearchSource(
        string $name,
        string $provider,
        string $description,
        array $siteFilters,
        string $querySuffix,
        array $companyNameSuffixes,
        array $allowedUrlContains,
        array $blockedUrlContains
    ): void {
        LeadSource::updateOrCreate(
            ['name' => $name],
            [
                'type' => 'search_api',
                'provider' => $provider,
                'description' => $description,
                'configuration' => [
                    'platform' => $provider,
                    'source_reference' => $provider,
                    'site_filters' => $siteFilters,
                    'query_suffix' => $querySuffix,
                    'company_name_suffixes' => $companyNameSuffixes,
                    'allowed_url_contains' => $allowedUrlContains,
                    'blocked_url_contains' => $blockedUrlContains,
                    'field_map' => [
                        'source_url' => ['url', 'link'],
                        'company_name' => ['company_name', 'name', 'title'],
                        'description' => ['description', 'snippet', 'content'],
                        'website' => ['website', 'company_website', 'official_website'],
                        'location' => ['location', 'address'],
                    ],
                ],
                'enabled' => (bool) config("lead_generation.sources.{$provider}.enabled", false),
            ]
        );
    }

    private function campaigns(array $countries): void
    {
        $cities = ['Amman', 'Zarqa', 'Irbid', 'Aqaba', 'Riyadh', 'Jeddah', 'Dubai', 'Abu Dhabi', 'Doha', 'Kuwait City'];
        $negativeKeywords = ['closed', 'permanently closed', 'free', 'jobs', 'careers', 'hiring', 'course', 'training jobs'];

        Campaign::where('name', 'Jordan Restaurants')->update(['enabled' => false]);

        $campaigns = [
            [
                'name' => 'Restaurants Cafes Delivery Buyers',
                'description' => 'Restaurants, cafes, bakeries, and cloud kitchens that need ordering, delivery, WhatsApp, loyalty, or POS systems.',
                'industries' => ['Restaurant', 'Cafe', 'Bakery', 'Cloud Kitchen', 'Catering'],
                'services' => ['Online Ordering System', 'WhatsApp Ordering', 'Food Delivery App', 'Restaurant POS'],
                'keywords' => ['new branch', 'delivery available', 'order online', 'table booking'],
            ],
            [
                'name' => 'Retail Ecommerce Growth Buyers',
                'description' => 'Retail shops, boutiques, supermarkets, and ecommerce brands that need online stores, inventory, CRM, or marketing automation.',
                'industries' => ['Retail', 'Fashion Store', 'Supermarket', 'Electronics Store', 'Ecommerce'],
                'services' => ['Ecommerce Website', 'Inventory System', 'CRM', 'Loyalty Program'],
                'keywords' => ['shop online', 'new collection', 'delivery available', 'branches'],
            ],
            [
                'name' => 'Clinics Healthcare Automation Buyers',
                'description' => 'Clinics, medical centers, dental clinics, labs, and pharmacies that need booking, patient management, reminders, or CRM.',
                'industries' => ['Clinic', 'Medical Center', 'Dental Clinic', 'Laboratory', 'Pharmacy'],
                'services' => ['Appointment Booking System', 'Patient Management System', 'WhatsApp Reminders', 'Clinic CRM'],
                'keywords' => ['book appointment', 'new clinic', 'medical center', 'insurance accepted'],
            ],
            [
                'name' => 'Beauty Wellness Booking Buyers',
                'description' => 'Salons, spas, barbershops, and beauty clinics that need booking, memberships, WhatsApp automation, or client retention.',
                'industries' => ['Beauty Salon', 'Spa', 'Barbershop', 'Beauty Clinic', 'Wellness Center'],
                'services' => ['Booking System', 'Client CRM', 'WhatsApp Automation', 'Membership System'],
                'keywords' => ['book now', 'new branch', 'appointments', 'offers'],
            ],
            [
                'name' => 'Real Estate Sales CRM Buyers',
                'description' => 'Real estate agencies, property developers, brokers, and property managers that need lead capture, CRM, portals, or automation.',
                'industries' => ['Real Estate Agency', 'Property Developer', 'Brokerage', 'Property Management'],
                'services' => ['Real Estate CRM', 'Property Portal', 'Lead Management System', 'Sales Automation'],
                'keywords' => ['new project', 'properties for sale', 'book viewing', 'real estate offers'],
            ],
            [
                'name' => 'Gyms Fitness Membership Buyers',
                'description' => 'Gyms, fitness studios, sports academies, and personal training businesses that need memberships, booking, apps, or retention.',
                'industries' => ['Gym', 'Fitness Center', 'Sports Academy', 'Personal Training Studio'],
                'services' => ['Membership System', 'Class Booking App', 'Fitness Mobile App', 'CRM'],
                'keywords' => ['join now', 'new classes', 'membership offer', 'personal training'],
            ],
            [
                'name' => 'Logistics Delivery Operations Buyers',
                'description' => 'Delivery companies, logistics firms, warehouses, and moving companies that need dispatch, tracking, fleet, or ERP systems.',
                'industries' => ['Logistics Company', 'Delivery Service', 'Warehouse', 'Moving Company', 'Courier'],
                'services' => ['Delivery Management System', 'Fleet Tracking', 'Dispatch Software', 'Operations Dashboard'],
                'keywords' => ['same day delivery', 'fleet', 'warehouse', 'tracking'],
            ],
            [
                'name' => 'Education Training Enrollment Buyers',
                'description' => 'Schools, nurseries, academies, and training centers that need enrollment, LMS, parent portals, CRM, or automation.',
                'industries' => ['School', 'Nursery', 'Training Center', 'Academy', 'Language Center'],
                'services' => ['Enrollment CRM', 'Learning Management System', 'Parent Portal', 'Student Management System'],
                'keywords' => ['registration open', 'new semester', 'courses available', 'admissions'],
            ],
            [
                'name' => 'Hotels Tourism Direct Booking Buyers',
                'description' => 'Hotels, travel agencies, tour operators, and event venues that need direct booking, CRM, websites, or guest automation.',
                'industries' => ['Hotel', 'Travel Agency', 'Tour Operator', 'Event Venue', 'Resort'],
                'services' => ['Booking Website', 'Reservation System', 'Guest CRM', 'WhatsApp Automation'],
                'keywords' => ['book now', 'packages', 'rooms available', 'event booking'],
            ],
            [
                'name' => 'Automotive Service CRM Buyers',
                'description' => 'Car dealerships, garages, rental companies, and service centers that need booking, inventory, CRM, or customer follow-up.',
                'industries' => ['Car Dealership', 'Auto Service Center', 'Car Rental', 'Garage', 'Spare Parts Store'],
                'services' => ['Automotive CRM', 'Service Booking System', 'Inventory System', 'Customer Follow Up'],
                'keywords' => ['book service', 'cars for sale', 'rental offers', 'maintenance'],
            ],
            [
                'name' => 'B2B Services Software Buyers',
                'description' => 'Agencies, consultants, accounting firms, legal offices, and B2B service companies that need CRM, portals, automation, or dashboards.',
                'industries' => ['Marketing Agency', 'Accounting Firm', 'Law Firm', 'Consulting Company', 'HR Company'],
                'services' => ['CRM', 'Client Portal', 'Workflow Automation', 'Admin Dashboard'],
                'keywords' => ['free consultation', 'book consultation', 'new service', 'business solutions'],
            ],
            [
                'name' => 'Construction Contracting ERP Buyers',
                'description' => 'Contractors, engineering firms, interior designers, and maintenance companies that need project management, CRM, ERP, or dashboards.',
                'industries' => ['Construction Company', 'Contractor', 'Engineering Firm', 'Interior Design', 'Maintenance Company'],
                'services' => ['Project Management System', 'ERP', 'CRM', 'Operations Dashboard'],
                'keywords' => ['new project', 'portfolio', 'maintenance services', 'fit out'],
            ],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::updateOrCreate(
                ['name' => $campaign['name']],
                [
                    'description' => $campaign['description'],
                    'countries' => $countries,
                    'cities' => $cities,
                    'industries' => $campaign['industries'],
                    'services' => $campaign['services'],
                    'keywords' => $campaign['keywords'],
                    'negative_keywords' => $negativeKeywords,
                    'minimum_score' => 55,
                    'enabled' => true,
                ]
            );
        }
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
