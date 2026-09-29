<?php

return [
    'search' => [
        'endpoint' => env('LEADS_SEARCH_ENDPOINT'),
        'method' => env('LEADS_SEARCH_METHOD', 'GET'),
        'query_parameter' => env('LEADS_SEARCH_QUERY_PARAMETER', 'q'),
        'results_path' => env('LEADS_SEARCH_RESULTS_PATH'),
        'results_paths' => [
            'items',
            'organic_results',
            'webPages.value',
            'web.results',
            'results',
        ],
        'api_key' => env('LEADS_SEARCH_API_KEY'),
        'api_key_header' => env('LEADS_SEARCH_API_KEY_HEADER'),
        'api_key_query_parameter' => env('LEADS_SEARCH_API_KEY_QUERY_PARAMETER'),
        'query' => array_merge(
            ['num' => (int) env('LEADS_SEARCH_RESULTS_PER_SEARCH', 100)],
            json_decode(env('LEADS_SEARCH_QUERY', '{}'), true) ?: []
        ),
        'headers' => json_decode(env('LEADS_SEARCH_HEADERS', '{}'), true) ?: [],
        'requests_per_run' => (int) env('LEADS_SEARCH_REQUESTS_PER_RUN', 1),
        'max_queries_per_run' => (int) env('LEADS_SEARCH_MAX_QUERIES_PER_RUN', 1),
        'monthly_limit' => (int) env('LEADS_SERPAPI_MONTHLY_LIMIT', 250),
        'cache_ttl_days' => (int) env('LEADS_SEARCH_CACHE_TTL_DAYS', 30),
        'dedupe_ttl_days' => (int) env('LEADS_SEARCH_DEDUPE_TTL_DAYS', 35),
        'min_quality_score' => (int) env('LEADS_SEARCH_MIN_QUALITY_SCORE', 10),
        'timeout' => (int) env('LEADS_SEARCH_TIMEOUT', 20),
        'user_agent' => env('LEADS_SEARCH_USER_AGENT'),
    ],

    'sources' => [
        'linkedin' => [
            'enabled' => (bool) env('LEADS_LINKEDIN_SOURCE_ENABLED', false),
        ],
        'facebook' => [
            'enabled' => (bool) env('LEADS_FACEBOOK_SOURCE_ENABLED', false),
        ],
        'facebook_groups' => [
            'enabled' => (bool) env('LEADS_FACEBOOK_GROUPS_SOURCE_ENABLED', env('LEADS_FACEBOOK_SOURCE_ENABLED', false)),
        ],
        'twitter' => [
            'enabled' => (bool) env('LEADS_TWITTER_SOURCE_ENABLED', false),
        ],
    ],
];
