<?php

namespace App\Services\LeadGeneration;

use App\Models\AiUsage;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class PipelineReportService
{
    public function dashboardStats(): array
    {
        return [
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', Lead::STATUS_NEW)->count(),
            'qualified_leads' => Lead::where('status', Lead::STATUS_QUALIFIED)->count(),
            'high_priority_leads' => Lead::whereIn('priority', [Lead::PRIORITY_HIGH, Lead::PRIORITY_CRITICAL])->count(),
            'contacted_leads' => Lead::where('status', Lead::STATUS_CONTACTED)->count(),
            'interested_leads' => Lead::where('status', Lead::STATUS_INTERESTED)->count(),
            'meetings' => Lead::where('status', Lead::STATUS_MEETING)->count(),
            'proposals' => Lead::where('status', Lead::STATUS_PROPOSAL)->count(),
            'won_deals' => Lead::where('status', Lead::STATUS_WON)->count(),
            'estimated_pipeline_value' => Lead::whereNotIn('status', [Lead::STATUS_LOST, Lead::STATUS_IGNORED])->sum(DB::raw('lead_score * 100')),
            'ai_cost_today' => AiUsage::whereDate('created_at', now()->toDateString())->sum('estimated_cost'),
            'ai_cost_month' => AiUsage::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('estimated_cost'),
        ];
    }

    public function chartData(): array
    {
        return [
            'by_day' => Lead::selectRaw('DATE(created_at) as label, COUNT(*) as total')->groupBy('label')->orderBy('label')->limit(30)->pluck('total', 'label'),
            'by_source' => Lead::selectRaw('COALESCE(source, "Unknown") as label, COUNT(*) as total')->groupBy('label')->pluck('total', 'label'),
            'by_country' => Lead::selectRaw('COALESCE(country, "Unknown") as label, COUNT(*) as total')->groupBy('label')->pluck('total', 'label'),
            'by_industry' => Lead::selectRaw('COALESCE(industry, "Unknown") as label, COUNT(*) as total')->groupBy('label')->pluck('total', 'label'),
            'by_score' => [
                '0-39' => Lead::whereBetween('lead_score', [0, 39])->count(),
                '40-59' => Lead::whereBetween('lead_score', [40, 59])->count(),
                '60-79' => Lead::whereBetween('lead_score', [60, 79])->count(),
                '80-100' => Lead::whereBetween('lead_score', [80, 100])->count(),
            ],
            'funnel' => [
                'new' => Lead::where('status', Lead::STATUS_NEW)->count(),
                'qualified' => Lead::where('status', Lead::STATUS_QUALIFIED)->count(),
                'contacted' => Lead::where('status', Lead::STATUS_CONTACTED)->count(),
                'replied' => Lead::where('status', Lead::STATUS_REPLIED)->count(),
                'interested' => Lead::where('status', Lead::STATUS_INTERESTED)->count(),
                'meeting' => Lead::where('status', Lead::STATUS_MEETING)->count(),
                'proposal' => Lead::where('status', Lead::STATUS_PROPOSAL)->count(),
                'won' => Lead::where('status', Lead::STATUS_WON)->count(),
            ],
        ];
    }
}
