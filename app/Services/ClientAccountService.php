<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;

/**
 * Single source of truth for client account balance and statement
 * calculations (PRD §71–72). Never duplicate this logic in controllers,
 * React pages, reports, or PDFs.
 *
 * Rules (PRD §33, §80):
 * - Project Total = SUM(project.amount WHERE status != cancelled)
 * - Net = Project Total - Payment Total (per currency, never combined)
 * - Net > 0 → Outstanding; Net < 0 → Credit = abs(Net)
 */
class ClientAccountService
{
    private const SCALE = 4;

    /**
     * Per-currency account summary for a client. Different currencies are
     * never mathematically combined (PRD §34–35).
     *
     * @return array{currencies: array<string, array{projects_total: string, payments_total: string, net: string, outstanding: string, credit: string}>, has_multiple_currencies: bool}
     */
    public function summary(Client $client): array
    {
        $projectsTotals = $client->projects()
            ->notCancelled()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        // Payments arrive in Phase 4; until then every client has paid nothing.
        $paymentsTotals = collect();

        $currencies = $projectsTotals
            ->keys()
            ->merge($paymentsTotals->keys())
            ->unique()
            ->values();

        $summary = [];

        foreach ($currencies as $currency) {
            $projectsRaw = (string) ($projectsTotals[$currency] ?? '0');
            $paymentsRaw = (string) ($paymentsTotals[$currency] ?? '0');

            $summary[$currency] = $this->buildLine(
                is_numeric($projectsRaw) ? $projectsRaw : '0',
                is_numeric($paymentsRaw) ? $paymentsRaw : '0',
            );
        }

        // A client with no projects still reports its default currency.
        if ($summary === []) {
            $summary[$client->currency] = $this->buildLine('0', '0');
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
    public function projectBalance(Project $project): string
    {
        // Assigned payments are introduced in Phase 4; until then the full
        // amount is outstanding. Raw DB value — never float (PRD §80.6).
        $raw = (string) $project->getRawOriginal('amount');

        return bcadd(is_numeric($raw) ? $raw : '0', '0', self::SCALE);
    }

    /**
     * @param  numeric-string  $projectsRaw
     * @param  numeric-string  $paymentsRaw
     * @return array{projects_total: string, payments_total: string, net: string, outstanding: string, credit: string}
     */
    private function buildLine(string $projectsRaw, string $paymentsRaw): array
    {
        $zero = bcadd('0', '0', self::SCALE);
        $projectsTotal = bcadd($projectsRaw, '0', self::SCALE);
        $paymentsTotal = bcadd($paymentsRaw, '0', self::SCALE);

        $net = bcsub($projectsTotal, $paymentsTotal, self::SCALE);

        $outstanding = bccomp($net, '0', self::SCALE) > 0 ? $net : $zero;
        $credit = bccomp($net, '0', self::SCALE) < 0 ? bcmul($net, '-1', self::SCALE) : $zero;

        return [
            'projects_total' => $projectsTotal,
            'payments_total' => $paymentsTotal,
            'net' => $net,
            'outstanding' => $outstanding,
            'credit' => $credit,
        ];
    }
}
