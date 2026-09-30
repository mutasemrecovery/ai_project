<?php

namespace Tests\Unit\LeadGeneration;

use App\Services\LeadGeneration\LeadDataNormalizer;
use PHPUnit\Framework\TestCase;

class LeadDataNormalizerTest extends TestCase
{
    public function test_it_normalizes_company_domain_email_and_phone(): void
    {
        $normalizer = new LeadDataNormalizer();

        $data = $normalizer->normalize([
            'company_name' => 'Recovery Company LLC',
            'website' => 'www.example.com/',
            'email' => 'INFO@EXAMPLE.COM',
            'phone' => '+962 79 123 4567',
        ]);

        $this->assertSame('recovery', $data['normalized_company_name']);
        $this->assertSame('https://www.example.com', $data['website']);
        $this->assertSame('example.com', $data['domain']);
        $this->assertSame('info@example.com', $data['email']);
        $this->assertSame('+962791234567', $data['phone']);
    }

    public function test_it_unwraps_google_redirect_urls(): void
    {
        $normalizer = new LeadDataNormalizer();

        $data = $normalizer->normalize([
            'website' => 'https://www.google.com/url?sa=t&url=https%3A%2F%2Fexample.com%2Fcontact%3Fref%3Dsearch&ved=abc',
        ]);

        $this->assertSame('https://example.com/contact?ref=search', $data['website']);
        $this->assertSame('example.com', $data['domain']);
    }
}
