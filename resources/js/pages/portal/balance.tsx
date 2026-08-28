import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import type { AccountSummary } from '@/types';

type PageProps = {
    summary: AccountSummary;
};

export default function PortalBalance() {
    const { summary } = usePage<PageProps>().props;

    return (
        <>
            <Head title="My Balance" />

            <div className="flex h-full flex-1 flex-col gap-4">
                <Heading
                    title="My Balance"
                    description="What your account owes, per currency"
                />

                {summary.has_multiple_currencies && (
                    <div className="rounded-xl border border-brand-ochre/50 bg-brand-ochre/10 p-3 text-sm">
                        This account uses multiple currencies. Amounts are shown
                        per currency and are never combined.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(summary.currencies).map(
                        ([currency, line]) => (
                            <Card key={currency}>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {line.credit !== 0
                                            ? 'Credit'
                                            : 'Outstanding'}{' '}
                                        · {currency}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-display">
                                        {line.credit !== 0
                                            ? formatMoney(line.credit, currency)
                                            : formatMoney(
                                                  line.outstanding,
                                                  currency,
                                              )}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Projects{' '}
                                        {formatMoney(
                                            line.projects_total,
                                            currency,
                                        )}{' '}
                                        · Paid{' '}
                                        {formatMoney(
                                            line.payments_total,
                                            currency,
                                        )}
                                    </p>
                                </CardContent>
                            </Card>
                        ),
                    )}
                </div>
            </div>
        </>
    );
}
