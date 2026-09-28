<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadGeneration\OutreachReviewRequest;
use App\Jobs\SendApprovedOutreachJob;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutreachController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->perm('outreach-table'))->only(['index', 'edit']);
        $this->middleware($this->perm('outreach-approve'))->only(['update', 'approve', 'reject', 'send']);
    }

    public function index(Request $request): View
    {
        $outreaches = Outreach::query()
            ->with(['lead', 'contact'])
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.outreaches.index', compact('outreaches'));
    }

    public function edit(Outreach $outreach): View
    {
        $outreach->load(['lead', 'contact']);

        return view('admin.outreaches.edit', compact('outreach'));
    }

    public function update(OutreachReviewRequest $request, Outreach $outreach): RedirectResponse
    {
        $outreach->update($request->only(['subject', 'body']));

        return back()->with('success', 'Outreach updated.');
    }

    public function approve(OutreachReviewRequest $request, Outreach $outreach): RedirectResponse
    {
        $outreach->update(array_merge($request->only(['subject', 'body']), [
            'status' => Outreach::STATUS_APPROVED,
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
            'rejected_at' => null,
        ]));

        if ($request->boolean('send_now')) {
            SendApprovedOutreachJob::dispatch($outreach->id);
        }

        return redirect()->route('admin.outreaches.index')->with('success', 'Outreach approved.');
    }

    public function reject(Outreach $outreach): RedirectResponse
    {
        $outreach->update([
            'status' => Outreach::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'Outreach rejected.');
    }

    public function send(Outreach $outreach): RedirectResponse
    {
        SendApprovedOutreachJob::dispatch($outreach->id);

        return back()->with('success', 'Approved outreach send queued.');
    }
}
