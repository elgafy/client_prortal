<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Project;
use App\Services\ClientAccountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ClientAccountService $accounts,
    ) {}

    /**
     * Admin dashboard: account-wide totals plus recent activity.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'summary' => $this->accounts->globalSummary(),
            'recentProjects' => Project::query()
                ->with('client:id,name')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentPayments' => Payment::query()
                ->active()
                ->with(['client:id,name', 'project:id,name'])
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
