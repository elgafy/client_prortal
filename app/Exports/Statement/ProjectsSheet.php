<?php

namespace App\Exports\Statement;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDiscount;
use App\Services\ClientAccountService;
use Illuminate\Support\Carbon;
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
            ->with('discounts')
            // Newest first; undated projects last (matches the statement).
            ->orderByRaw('project_date IS NULL, project_date DESC')
            ->orderByDesc('id')
            ->get()
            ->map(function ($project): array {
                $balance = $this->accounts->projectBalance($project);

                return [
                    $project->name,
                    $project->project_date
                        ? Carbon::parse($project->project_date)->toFormattedDateString()
                        : null,
                    $project->description,
                    (int) $project->subtotal,
                    $this->discountCell($project),
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
        return ['Project', 'Date', 'Description', 'Subtotal', 'Discount', 'Amount', 'Currency', 'Status', 'Assigned Payments', 'Project Balance'];
    }

    public function title(): string
    {
        return 'Projects';
    }

    /**
     * The discount cell: empty when the project has no discounts, otherwise
     * the total followed by each row's data, e.g.
     * "-1,000 (Early payment: -500; Penalty: -200 (10%))".
     */
    private function discountCell(Project $project): ?string
    {
        $discounts = $project->discounts;

        if ($discounts->isEmpty()) {
            return null;
        }

        $details = $discounts
            ->map(function (ProjectDiscount $discount) use ($project): string {
                $value = '-'.number_format((float) $discount->valueFor((int) $project->subtotal));

                return $discount->mode === ProjectDiscount::MODE_PERCENTAGE
                    ? sprintf('%s: %s (%s%%)', $discount->title, $value, $discount->percentage)
                    : sprintf('%s: %s', $discount->title, $value);
            })
            ->implode('; ');

        return sprintf('-%s (%s)', number_format((float) $project->discount_total), $details);
    }
}
