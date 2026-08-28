/** Format an integer amount (whole currency units) as currency for display. */
export function formatMoney(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}

/**
 * Format an ISO date string (e.g. "2025-07-16") in a human readable way.
 * Returns an em dash for empty values.
 */
export function formatDate(date: string | null | undefined): string {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(date),
    );
}
