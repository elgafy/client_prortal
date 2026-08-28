import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import {
    create as paymentsCreate,
    index as paymentsIndex,
    show as paymentsShow,
} from '@/routes/payments';
import type { Paginator, Payment } from '@/types';

type PageProps = {
    payments: Paginator<Payment>;
    filters: { search?: string };
};

export default function PaymentsIndex() {
    const { payments, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search === (filters.search ?? '')) {
                return;
            }

            router.get(
                paymentsIndex.url(),
                { search },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <>
            <Head title="Payments" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Payments"
                        description="What clients have paid"
                    />
                    <Button asChild>
                        <Link href={paymentsCreate()}>
                            <Plus />
                            New Payment
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search client, project, method…"
                        className="pl-9"
                    />
                </div>

                {payments.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        {filters.search
                            ? 'No payments match your search.'
                            : 'No payments yet. Record your first payment to get started.'}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Project</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.data.map((payment) => (
                                    <TableRow
                                        key={payment.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.visit(
                                                paymentsShow(payment.id),
                                            )
                                        }
                                    >
                                        <TableCell>
                                            {formatDate(payment.payment_date)}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {payment.client?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                payment.amount,
                                                payment.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>{payment.method}</TableCell>
                                        <TableCell>
                                            {payment.project?.name ??
                                                'Account payment'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    payment.status === 'active'
                                                        ? 'secondary'
                                                        : 'destructive'
                                                }
                                            >
                                                {payment.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {payments.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Showing {payments.from}–{payments.to} of{' '}
                            {payments.total}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={payments.current_page <= 1}
                                onClick={() =>
                                    router.visit(
                                        paymentsIndex.url({
                                            query: {
                                                page: payments.current_page - 1,
                                                search,
                                            },
                                        }),
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={
                                    payments.current_page >= payments.last_page
                                }
                                onClick={() =>
                                    router.visit(
                                        paymentsIndex.url({
                                            query: {
                                                page: payments.current_page + 1,
                                                search,
                                            },
                                        }),
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

PaymentsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Payments', href: '/payments' },
    ],
};
