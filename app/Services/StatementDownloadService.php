<?php

namespace App\Services;

use App\Exports\Statement\StatementExport;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Renders account statements as downloadable PDF and Excel files (PRD §27).
 */
class StatementDownloadService
{
    public function __construct(
        private readonly ClientAccountService $accounts,
    ) {}

    public function pdf(Client $client, ?string $from = null, ?string $to = null): Response
    {
        $pdf = Pdf::loadView('statements.pdf', [
            'businessName' => config('app.name'),
            'client' => $client,
            'from' => $from,
            'to' => $to,
            ...$this->accounts->statement($client, $from, $to),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($client, 'pdf').'"',
        ]);
    }

    public function excel(Client $client, ?string $from = null, ?string $to = null): BinaryFileResponse
    {
        return Excel::download(
            new StatementExport($client, $this->accounts, $from, $to),
            $this->filename($client, 'xlsx'),
        );
    }

    private function filename(Client $client, string $extension): string
    {
        $slug = str($client->name)->slug();
        $date = now()->toDateString();

        return "statement-{$slug}-{$date}.{$extension}";
    }
}
