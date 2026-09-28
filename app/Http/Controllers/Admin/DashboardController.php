<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeadGeneration\PipelineReportService;

class DashboardController extends Controller
{

    public function index(PipelineReportService $reports)
    {
        return view('admin.dashboard', [
            'stats' => $reports->dashboardStats(),
            'charts' => $reports->chartData(),
        ]);
    }
}
