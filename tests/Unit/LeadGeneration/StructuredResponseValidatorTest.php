<?php

namespace Tests\Unit\LeadGeneration;

use App\Services\LeadGeneration\Ai\LeadAiSchemas;
use App\Services\LeadGeneration\Ai\StructuredResponseValidator;
use PHPUnit\Framework\TestCase;

class StructuredResponseValidatorTest extends TestCase
{
    public function test_it_validates_reply_classification_schema(): void
    {
        $validator = new StructuredResponseValidator();
        $errors = $validator->validate([
            'category' => 'unsubscribe',
            'confidence' => 0.95,
            'reasoning' => 'The reply asks to stop messages.',
            'do_not_contact' => true,
        ], LeadAiSchemas::replyClassification());

        $this->assertSame([], $errors);
    }

    public function test_it_rejects_unknown_enum_values(): void
    {
        $validator = new StructuredResponseValidator();
        $errors = $validator->validate([
            'category' => 'maybe',
            'confidence' => 0.5,
            'reasoning' => 'test',
            'do_not_contact' => false,
        ], LeadAiSchemas::replyClassification());

        $this->assertNotEmpty($errors);
    }
}
