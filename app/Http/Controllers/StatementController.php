<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientAccountService;
use App\Services\StatementDownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StatementController extends Controller
{
    public function __construct(
        private readonly ClientAccountService $accounts,
        private readonly StatementDownloadService $downloads,
    ) {}

    /**
     * Account statement page (PRD §26). The optional date range filters the
     * payment history only — totals always reflect the complete account (§32).
     */
    public function show(Request $request, Client $client): \Inertia\Response
    {
        $this->authorize('view', $client);

        [$from, $to] = $this->dateRange($request);

        return Inertia::render('clients/statement', [
            'client' => $client,
            'statement' => $this->accounts->statement($client, $from, $to),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function pdf(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        [$from, $to] = $this->dateRange($request);

        return $this->downloads->pdf($client, $from, $to);
    }

    public function excel(Request $request, Client $client): BinaryFileResponse
    {
        $this->authorize('view', $client);

        [$from, $to] = $this->dateRange($request);

        return $this->downloads->excel($client, $from, $to);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(Request $request): array
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        return [$from, $to];
    }
}
