<?php

namespace App\Exports\Statement;

use App\Models\Client;
use App\Services\ClientAccountService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StatementExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly Client $client,
        private readonly ClientAccountService $accounts,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->client, $this->accounts->summary($this->client)),
            new ProjectsSheet($this->client, $this->accounts),
            new PaymentsSheet($this->client, $this->from, $this->to),
        ];
    }
}
