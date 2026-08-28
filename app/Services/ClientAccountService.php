<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;

/**
 * Single source of truth for client account balance and statement
 * calculations (PRD §71–72). Never duplicate this logic in controllers,
 * React pages, reports, or PDFs.
 *
 * Rules (PRD §33, §80):
 * - Project Total = SUM(project.amount WHERE status != cancelled)
 * - Payment Total = SUM(payment.amount WHERE status = active)
 * - Net = Project Total - Payment Total (per currency, never combined)
 * - Net > 0 → Outstanding; Net < 0 → Credit = abs(Net)
 *
 * All amounts are integers (whole currency units) — no decimals anywhere.
 */
class ClientAccountService
{
    /**
     * Per-currency account summary for a client. Different currencies are
     * never mathematically combined (PRD §34–35).
     *
     * @return array{currencies: array<string, array{projects_total: int, payments_total: int, net: int, outstanding: int, credit: int}>, has_multiple_currencies: bool}
     */
    public function summary(Client $client): array
    {
        $projectsTotals = $client->projects()
            ->notCancelled()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $paymentsTotals = $client->payments()
            ->active()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $currencies = $projectsTotals
            ->keys()
            ->merge($paymentsTotals->keys())
            ->unique()
            ->values();

        $summary = [];

        foreach ($currencies as $currency) {
            $summary[$currency] = $this->buildLine(
                (int) ($projectsTotals[$currency] ?? 0),
                (int) ($paymentsTotals[$currency] ?? 0),
            );
        }

        // A client with no projects still reports its default currency.
        if ($summary === []) {
            $summary[$client->currency] = $this->buildLine(0, 0);
        }

        return [
            'currencies' => $summary,
            'has_multiple_currencies' => count($summary) > 1,
        ];
    }

    /**
     * Account-wide totals across all clients, per currency. Used by the
     * admin dashboard. Same rules as summary() — cancelled projects and
     * voided payments are excluded.
     *
     * @return array{currencies: array<string, array{projects_total: int, payments_total: int, net: int, outstanding: int, credit: int}>, has_multiple_currencies: bool}
     */
    public function globalSummary(): array
    {
        $projectsTotals = Project::query()
            ->notCancelled()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $paymentsTotals = Payment::query()
            ->active()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $currencies = $projectsTotals
            ->keys()
            ->merge($paymentsTotals->keys())
            ->unique()
            ->values();

        $summary = [];

        foreach ($currencies as $currency) {
            $summary[$currency] = $this->buildLine(
                (int) ($projectsTotals[$currency] ?? 0),
                (int) ($paymentsTotals[$currency] ?? 0),
            );
        }

        return [
            'currencies' => $summary,
            'has_multiple_currencies' => count($summary) > 1,
        ];
    }

    /**
     * Balance remaining on a single project: amount minus active payments
     * assigned to it. Unassigned payments never affect project balances (PRD §14).
     */
    public function projectBalance(Project $project): int
    {
        $assigned = (int) $project->payments()
            ->active()
            ->sum('amount');

        return (int) $project->amount - $assigned;
    }

    /**
     * @return array{projects_total: int, payments_total: int, net: int, outstanding: int, credit: int}
     */
    private function buildLine(int $projectsTotal, int $paymentsTotal): array
    {
        $net = $projectsTotal - $paymentsTotal;

        return [
            'projects_total' => $projectsTotal,
            'payments_total' => $paymentsTotal,
            'net' => $net,
            'outstanding' => max($net, 0),
            'credit' => max(-$net, 0),
        ];
    }
}
