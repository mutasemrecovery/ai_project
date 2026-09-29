<?php

namespace Tests\Unit\LeadGeneration;

use App\Services\LeadGeneration\Ai\AiService;
use App\Services\LeadGeneration\LeadAnalysisService;
use App\Services\LeadGeneration\LeadScoringService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LeadAnalysisServiceTest extends TestCase
{
    public function test_it_requires_real_buyer_intent_before_qualification(): void
    {
        $service = new LeadAnalysisService(
            $this->createMock(AiService::class),
            new LeadScoringService()
        );

        $this->assertFalse($this->invoke($service, 'isQualifiedBuyer', [[
            'is_potential_client' => true,
            'confidence' => 0.9,
            'detected_needs' => [],
            'recommended_services' => ['نظام CRM'],
            'signals' => [
                ['signal' => 'ذكر CRM فقط', 'evidence' => 'CRM', 'confidence' => 0.9],
            ],
        ]]));

        $this->assertTrue($this->invoke($service, 'isQualifiedBuyer', [[
            'is_potential_client' => true,
            'confidence' => 0.82,
            'detected_needs' => ['يحتاج إلى نظام حجز مواعيد'],
            'recommended_services' => ['نظام حجز مواعيد'],
            'signals' => [
                ['signal' => 'طلب حجز واضح', 'evidence' => 'احجز موعد', 'confidence' => 0.8],
            ],
        ]]));
    }

    public function test_it_builds_arabic_summary(): void
    {
        $service = new LeadAnalysisService(
            $this->createMock(AiService::class),
            new LeadScoringService()
        );

        $summary = $this->invoke($service, 'summary', [[
            'is_potential_client' => true,
            'detected_needs' => ['نظام طلبات'],
            'recommended_services' => ['متجر إلكتروني'],
        ]]);

        $this->assertSame('عميل محتمل: نعم. الاحتياجات: نظام طلبات. الخدمات المقترحة: متجر إلكتروني.', $summary);
    }

    private function invoke(object $object, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
