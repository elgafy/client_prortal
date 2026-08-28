<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends PortalController
{
    public function __invoke(Request $request): Response
    {
        $client = $this->client($request);

        return Inertia::render('portal/dashboard', [
            'summary' => $this->accounts()->summary($client),
            'recentProjects' => $client->projects()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentPayments' => $client->payments()
                ->active()
                ->with('project:id,name')
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
