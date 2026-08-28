<?php

namespace App\Exports\Statement;

use App\Models\Client;
use App\Services\ClientAccountService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectsSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Client $client,
        private readonly ClientAccountService $accounts,
    ) {}

    /**
     * @return array<array<int|string|null>>
     */
    public function array(): array
    {
        return $this->client->projects()
            ->orderBy('name')
            ->get()
            ->map(function ($project): array {
                $balance = $this->accounts->projectBalance($project);

                return [
                    $project->name,
                    $project->description,
                    (int) $project->amount,
                    $project->currency,
                    $project->status,
                    (int) $project->amount - $balance,
                    $balance,
                ];
            })
            ->all();
    }

    public function headings(): array
    {
        return ['Project', 'Description', 'Amount', 'Currency', 'Status', 'Assigned Payments', 'Project Balance'];
    }

    public function title(): string
    {
        return 'Projects';
    }
}
