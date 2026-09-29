<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Services\LeadGeneration\Ai\AiService;
use App\Services\LeadGeneration\Ai\LeadAiSchemas;

class LeadAnalysisService
{
    public function __construct(
        private AiService $ai,
        private LeadScoringService $scoring
    ) {
    }

    public function analyze(Lead $lead): Lead
    {
        $lead->forceFill(['status' => Lead::STATUS_ANALYZING])->save();

        $response = $this->ai->generateJson(
            'company_analysis',
            $this->systemPrompt(),
            $this->userPrompt($lead),
            LeadAiSchemas::companyAnalysis(),
            $lead->id
        );

        $analysis = $response->data;
        $scores = $this->scoring->score($lead, $analysis);
        $minimumScore = (int) ($lead->campaign?->minimum_score ?? 55);
        $status = $this->isQualifiedBuyer($analysis) && $scores['lead_score'] >= $minimumScore
            ? Lead::STATUS_QUALIFIED
            : Lead::STATUS_IGNORED;

        $lead->update(array_merge($scores, [
            'industry' => $lead->industry ?: $analysis['industry'],
            'detected_need' => implode(', ', $analysis['detected_needs']),
            'detected_services' => $analysis['recommended_services'],
            'business_signals' => $analysis['signals'],
            'ai_summary' => $this->summary($analysis),
            'ai_reasoning' => $analysis['reasoning'],
            'status' => $status,
        ]));

        return $lead->refresh();
    }

    private function systemPrompt(): string
    {
        return 'You qualify public business leads for a software development company. Return all human-readable values in Arabic only, including industry, detected_needs, recommended_services, signals.signal, signals.evidence, project_type, and reasoning. Do not invent facts. Every important signal must include evidence from the provided lead data or source URLs. Qualify only businesses that show explicit buying intent, an operational pain, an expansion event, or a clear digital gap that a software vendor can solve. Treat public customer calls-to-action such as book now, order online, bookings via our website, tickets, events, packages, or offers as not qualified unless the evidence says their booking/order process is manual, broken, missing, or they are looking for a vendor. Treat job posts, hiring requirements, login pages, marketplace listings, and companies advertising their own CRM/ERP/software products as not qualified unless the lead explicitly asks for an external software development partner. Do not recommend services from keyword overlap alone; only recommend a service when the evidence shows a business problem, operational gap, or buying intent. If the evidence is weak, set is_potential_client=false, detected_needs=[], recommended_services=[], and explain briefly in Arabic.';
    }

    private function userPrompt(Lead $lead): string
    {
        return json_encode([
            'company_name' => $lead->company_name,
            'website' => $lead->website,
            'country' => $lead->country,
            'city' => $lead->city,
            'industry' => $lead->industry,
            'description' => $lead->description,
            'source' => $lead->source,
            'source_url' => $lead->source_url,
            'source_reference' => $lead->source_reference,
            'campaign' => $lead->campaign?->name,
            'campaign_minimum_score' => $lead->campaign?->minimum_score,
            'output_language' => 'Arabic',
            'qualification_rule' => 'اعتبره مؤهلا فقط إذا كان هناك احتياج فعلي أو اهتمام شراء واضح، وليس مجرد ذكر كلمات مثل CRM أو ERP أو software.',
            'target_services' => [
                'تطوير Laravel',
                'تطوير PHP',
                'تطبيقات Flutter',
                'تطوير API',
                'لوحات تحكم إدارية',
                'نظام CRM',
                'نظام ERP',
                'تطوير SaaS',
                'تكامل ذكاء اصطناعي',
                'أتمتة بالذكاء الاصطناعي',
                'تكامل واتساب',
                'أنظمة طلبات للمطاعم',
                'أنظمة حجز',
                'متاجر إلكترونية',
                'برمجيات أعمال مخصصة',
            ],
            'pre_ai_business_signals' => $lead->business_signals ?: [],
        ], JSON_PRETTY_PRINT);
    }

    private function isQualifiedBuyer(array $analysis): bool
    {
        if (! ($analysis['is_potential_client'] ?? false)) {
            return false;
        }

        if ((float) ($analysis['confidence'] ?? 0) < 0.65) {
            return false;
        }

        if (empty($analysis['detected_needs']) || empty($analysis['recommended_services'])) {
            return false;
        }

        return collect($analysis['signals'] ?? [])
            ->contains(fn ($signal) => is_array($signal) && (float) ($signal['confidence'] ?? 0) >= 0.7);
    }

    private function summary(array $analysis): string
    {
        return trim(sprintf(
            'عميل محتمل: %s. الاحتياجات: %s. الخدمات المقترحة: %s.',
            $analysis['is_potential_client'] ? 'نعم' : 'لا',
            implode('، ', $analysis['detected_needs']),
            implode('، ', $analysis['recommended_services'])
        ));
    }
}
