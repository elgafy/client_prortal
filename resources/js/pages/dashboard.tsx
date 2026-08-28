import { Head, router, usePage } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import { show as paymentsShow } from '@/routes/payments';
import { show as projectsShow } from '@/routes/projects';
import type { AccountSummary, Payment, Project } from '@/types';

type PageProps = {
    summary: AccountSummary;
    recentProjects: Project[];
    recentPayments: Payment[];
};

export default function Dashboard() {
    const { summary, recentProjects, recentPayments } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Dashboard"
                    description="Account-wide totals across all clients"
                />

                {summary.has_multiple_currencies && (
                    <div className="flex items-center gap-2 rounded-xl border border-brand-ochre/50 bg-brand-ochre/10 p-3 text-sm text-foreground">
                        <TriangleAlert className="size-4 shrink-0 text-brand-ochre" />
                        Totals are shown per currency and are never combined.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {Object.entries(summary.currencies).map(([currency, line]) => (
                        <Card key={currency}>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {line.credit !== 0 ? 'Credit' : 'Outstanding'} ·{' '}
                                    {currency}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="font-display text-2xl">
                                    {line.credit !== 0
                                        ? formatMoney(line.credit, currency)
                                        : formatMoney(line.outstanding, currency)}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Projects {formatMoney(line.projects_total, currency)} ·
                                    Paid {formatMoney(line.payments_total, currency)}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-3">
                        <Heading variant="small" title="Recent Projects" />
                        {recentProjects.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No projects yet.
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Project</TableHead>
                                            <TableHead>Client</TableHead>
                                            <TableHead>Amount</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentProjects.map((project) => (
                                            <TableRow
                                                key={project.id}
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    router.visit(projectsShow(project.id))
                                                }
                                            >
                                                <TableCell className="font-medium">
                                                    {project.name}
                                                </TableCell>
                                                <TableCell>
                                                    {project.client?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(project.amount, project.currency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>

                    <div className="space-y-3">
                        <Heading variant="small" title="Recent Payments" />
                        {recentPayments.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No payments yet.
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Client</TableHead>
                                            <TableHead>Amount</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentPayments.map((payment) => (
                                            <TableRow
                                                key={payment.id}
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    router.visit(paymentsShow(payment.id))
                                                }
                                            >
                                                <TableCell>
                                                    {formatDate(payment.payment_date)}
                                                </TableCell>
                                                <TableCell>
                                                    {payment.client?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(payment.amount, payment.currency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
};
