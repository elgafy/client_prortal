<?php

namespace App\Http\Controllers\Portal;

use App\Services\ClientAccountService;
use App\Services\StatementDownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StatementController extends PortalController
{
    public function __construct(
        private readonly StatementDownloadService $downloads,
        ClientAccountService $accounts,
    ) {
        parent::__construct($accounts);
    }

    /**
     * The client's own account statement (PRD §27).
     */
    public function show(Request $request): Response
    {
        $client = $this->client($request);
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('portal/statement', [
            'client' => $client->only(['name', 'company_name']),
            'statement' => $this->accounts()->statement($client, $from, $to),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        [$from, $to] = $this->dateRange($request);

        return $this->downloads->pdf($this->client($request), $from, $to);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        return $this->downloads->excel($this->client($request), $from, $to);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(Request $request): array
    {
        return [
            $request->date('from')?->toDateString(),
            $request->date('to')?->toDateString(),
        ];
    }
}
