import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import type { Payment } from '@/types';

type PageProps = {
    payments: Payment[];
};

export default function PortalPayments() {
    const { payments } = usePage<PageProps>().props;

    return (
        <>
            <Head title="My Payments" />

            <div className="flex h-full flex-1 flex-col gap-4">
                <Heading
                    title="My Payments"
                    description="Your payment history"
                />

                {payments.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        No payments yet.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Project</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.map((payment) => (
                                    <TableRow
                                        key={payment.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.visit(
                                                `/portal/payments/${payment.id}`,
                                            )
                                        }
                                    >
                                        <TableCell>
                                            {formatDate(payment.payment_date)}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                payment.amount,
                                                payment.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {payment.currency}
                                        </TableCell>
                                        <TableCell>{payment.method}</TableCell>
                                        <TableCell>
                                            {payment.project?.name ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}
