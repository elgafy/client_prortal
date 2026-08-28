<?php

namespace App\Exports\Statement;

use App\Models\Client;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PaymentsSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Client $client,
        private readonly ?string $from,
        private readonly ?string $to,
    ) {}

    /**
     * @return array<array<int|string|null>>
     */
    public function array(): array
    {
        return $this->client->payments()
            ->active()
            ->with('project:id,name')
            ->when($this->from, fn (Builder $query) => $query->whereDate('payment_date', '>=', $this->from))
            ->when($this->to, fn (Builder $query) => $query->whereDate('payment_date', '<=', $this->to))
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->map(fn ($payment): array => [
                $payment->payment_date,
                (int) $payment->amount,
                $payment->currency,
                $payment->method,
                $payment->project?->name,
                $payment->received_by,
                $payment->note,
            ])
            ->all();
    }

    public function headings(): array
    {
        return ['Date', 'Amount', 'Currency', 'Method', 'Project', 'Received By', 'Note'];
    }

    public function title(): string
    {
        return 'Payments';
    }
}
