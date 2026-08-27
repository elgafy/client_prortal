/** Format a decimal string (e.g. "1234.5000") as currency for display. */
export function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(Number(amount));
}
