<?php

namespace App\Exports\Statement;

use App\Models\Client;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
            ->with(['comments' => fn (Builder $query) => $query
                ->where('is_internal', false)
                ->with('user:id,name')
                ->orderBy('created_at')
                ->orderBy('id')])
            ->when($this->from, fn (Builder $query) => $query->whereDate('payment_date', '>=', $this->from))
            ->when($this->to, fn (Builder $query) => $query->whereDate('payment_date', '<=', $this->to))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($payment): array => [
                Carbon::parse($payment->payment_date)->toFormattedDateString(),
                (int) $payment->amount,
                $payment->currency,
                $payment->method,
                $payment->received_from,
                $payment->project?->name,
                $payment->note,
                $payment->comments->isEmpty() ? null : $payment->comments
                    ->map(fn ($comment): string => ($comment->user->name ?? 'Unknown').': '.$comment->body)
                    ->implode(", "),
            ])
            ->all();
    }

    public function headings(): array
    {
        return ['Date', 'Amount', 'Currency', 'Method', 'Received From', 'Project', 'Note', 'Comments'];
    }

    public function title(): string
    {
        return 'Payments';
    }
}
