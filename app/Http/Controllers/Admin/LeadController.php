<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadGeneration\StoreManualLeadRequest;
use App\Http\Requests\LeadGeneration\UpdateLeadStatusRequest;
use App\Jobs\AnalyzeLeadJob;
use App\Jobs\GenerateOutreachJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Repositories\LeadGeneration\LeadRepository;
use App\Services\LeadGeneration\LeadIntakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->perm('lead-table'))->only(['index', 'show']);
        $this->middleware($this->perm('lead-add'))->only(['create', 'store']);
        $this->middleware($this->perm('lead-edit'))->only(['updateStatus', 'approve', 'ignore', 'analyze', 'generateOutreach']);
    }

    public function index(Request $request, LeadRepository $leads): View
    {
        $filters = $request->only([
            'country',
            'city',
            'industry',
            'minimum_score',
            'maximum_score',
            'priority',
            'status',
            'source',
            'campaign_id',
            'search',
        ]);

        return view('admin.leads.index', [
            'leads' => $leads->paginate($filters, 20),
            'campaigns' => Campaign::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.leads.create', [
            'campaigns' => Campaign::orderBy('name')->get(),
        ]);
    }

    public function store(StoreManualLeadRequest $request, LeadIntakeService $intake): RedirectResponse
    {
        $source = LeadSource::firstOrCreate(
            ['name' => 'Manual Entry'],
            ['type' => 'manual', 'enabled' => true]
        );

        $rawLead = $intake->storeRawLead($request->rawLeadData($source->id));
        $lead = $intake->promoteRawLead($rawLead);

        return redirect()->route('admin.leads.show', $lead)->with('success', __('messages.lg_success_lead_saved'));
    }

    public function show(Lead $lead): View
    {
        $lead->load([
            'leadSource',
            'campaign',
            'icp',
            'companyProfile',
            'contacts',
            'outreaches.contact',
            'followUps',
            'aiUsages',
            'rawLeads',
        ]);

        return view('admin.leads.show', compact('lead'));
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update([
            'status' => $request->input('status'),
            'priority' => $request->input('priority', $lead->priority),
            'do_not_contact' => $request->boolean('do_not_contact'),
        ]);

        return back()->with('success', __('messages.lg_success_lead_status_updated'));
    }

    public function analyze(Lead $lead): RedirectResponse
    {
        AnalyzeLeadJob::dispatch($lead->id);

        return back()->with('success', __('messages.lg_success_lead_analysis_queued'));
    }

    public function generateOutreach(Request $request, Lead $lead): RedirectResponse
    {
        $type = $request->input('message_type', 'professional_email');
        GenerateOutreachJob::dispatch($lead->id, $type);

        return back()->with('success', __('messages.lg_success_outreach_generation_queued'));
    }

    public function approve(Lead $lead): RedirectResponse
    {
        $lead->update(['status' => Lead::STATUS_APPROVED]);

        return back()->with('success', __('messages.lg_success_lead_approved'));
    }

    public function ignore(Lead $lead): RedirectResponse
    {
        $lead->update(['status' => Lead::STATUS_IGNORED]);

        return back()->with('success', __('messages.lg_success_lead_ignored'));
    }
}
