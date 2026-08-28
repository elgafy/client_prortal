<?php

namespace App\Exports\Statement;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param  array{currencies: array<string, array{projects_total: int, payments_total: int, net: int, outstanding: int, credit: int}>, has_multiple_currencies: bool}  $summary
     */
    public function __construct(
        private readonly Client $client,
        private readonly array $summary,
    ) {}

    /**
     * @return array<array<int|string|null>>
     */
    public function array(): array
    {
        $rows = [
            ['Client', $this->client->name],
            ['Company', $this->client->company_name],
            ['Statement generated on', now()->toFormattedDateString()],
            [],
            ['Currency', 'Total Projects', 'Total Payments', 'Outstanding', 'Credit'],
        ];

        foreach ($this->summary['currencies'] as $currency => $line) {
            $rows[] = [
                $currency,
                $line['projects_total'],
                $line['payments_total'],
                $line['outstanding'],
                $line['credit'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Summary';
    }
}
