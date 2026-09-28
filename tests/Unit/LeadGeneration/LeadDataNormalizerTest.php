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
}
