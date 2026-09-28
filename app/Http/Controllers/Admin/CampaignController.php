<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadGeneration\StoreCampaignRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->perm('campaign-table'))->only(['index']);
        $this->middleware($this->perm('campaign-add'))->only(['create', 'store']);
        $this->middleware($this->perm('campaign-edit'))->only(['edit', 'update']);
        $this->middleware($this->perm('campaign-delete'))->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $campaigns = Campaign::query()
            ->when($request->search, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('admin.campaigns.create', ['campaign' => new Campaign()]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        Campaign::create($request->campaignData());

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(Campaign $campaign): View
    {
        return view('admin.campaigns.edit', compact('campaign'));
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->update($request->campaignData());

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('success', 'Campaign deleted.');
    }
}
