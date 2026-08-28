import { Head, usePage } from '@inertiajs/react';
import { FileDown, FileSpreadsheet } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import type { AccountSummary, Client, Payment, Project } from '@/types';

type PageProps = {
    client: Client;
    statement: {
        summary: AccountSummary;
        projects: Project[];
        payments: Payment[];
        generatedAt: string;
    };
    filters: { from: string | null; to: string | null };
};

export default function ClientStatement() {
    const { client, statement, filters } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Statement · ${client.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Account Statement"
                        description={client.name}
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={`/clients/${client.id}/statement/pdf?from=${filters.from ?? ''}&to=${filters.to ?? ''}`}
                            >
                                <FileDown />
                                PDF
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a
                                href={`/clients/${client.id}/statement/excel?from=${filters.from ?? ''}&to=${filters.to ?? ''}`}
                            >
                                <FileSpreadsheet />
                                Excel
                            </a>
                        </Button>
                    </div>
                </div>

                <form
                    method="get"
                    className="flex flex-wrap items-end gap-3 rounded-xl border p-4"
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="from">Payments from</Label>
                        <Input
                            id="from"
                            name="from"
                            type="date"
                            defaultValue={filters.from ?? ''}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="to">Payments to</Label>
                        <Input
                            id="to"
                            name="to"
                            type="date"
                            defaultValue={filters.to ?? ''}
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Apply Filter
                    </Button>
                    <p className="text-xs text-muted-foreground">
                        Totals always reflect complete account totals (PRD §32).
                    </p>
                </form>

                {statement.summary.has_multiple_currencies && (
                    <div className="rounded-xl border border-brand-ochre/50 bg-brand-ochre/10 p-3 text-sm">
                        This account uses multiple currencies. Sections are
                        shown per currency and are never combined.
                    </div>
                )}

                {Object.entries(statement.summary.currencies).map(
                    ([currency, line]) => {
                        const currencyProjects = statement.projects.filter(
                            (p) => p.currency === currency,
                        );
                        const currencyPayments = statement.payments.filter(
                            (p) => p.currency === currency,
                        );

                        return (
                            <Card key={currency}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        Currency: {currency}
                                        <Badge variant="secondary">
                                            {line.credit !== 0
                                                ? 'Credit'
                                                : 'Outstanding'}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div>
                                        <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                                            Projects
                                        </h3>
                                        {currencyProjects.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No projects.
                                            </p>
                                        ) : (
                                            <div className="overflow-hidden rounded-xl border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>
                                                                Project
                                                            </TableHead>
                                                            <TableHead>
                                                                Date
                                                            </TableHead>
                                                            <TableHead className="text-right">
                                                                Amount
                                                            </TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {currencyProjects.map(
                                                            (project) => (
                                                                <TableRow
                                                                    key={
                                                                        project.id
                                                                    }
                                                                >
                                                                    <TableCell className="font-medium">
                                                                        {
                                                                            project.name
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell>
                                                                        {formatDate(
                                                                            project.project_date,
                                                                        )}
                                                                    </TableCell>
                                                                    <TableCell className="text-right">
                                                                        {formatMoney(
                                                                            project.amount,
                                                                            currency,
                                                                        )}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ),
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        )}
                                    </div>

                                    <div>
                                        <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                                            Payments
                                        </h3>
                                        {currencyPayments.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No payments.
                                            </p>
                                        ) : (
                                            <div className="overflow-hidden rounded-xl border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>
                                                                Date
                                                            </TableHead>
                                                            <TableHead>
                                                                Method
                                                            </TableHead>
                                                            <TableHead>
                                                                Project
                                                            </TableHead>
                                                            <TableHead className="text-right">
                                                                Amount
                                                            </TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {currencyPayments.map(
                                                            (payment) => (
                                                                <TableRow
                                                                    key={
                                                                        payment.id
                                                                    }
                                                                >
                                                                    <TableCell>
                                                                        {formatDate(
                                                                            payment.payment_date,
                                                                        )}
                                                                    </TableCell>
                                                                    <TableCell>
                                                                        {
                                                                            payment.method
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell>
                                                                        {payment
                                                                            .project
                                                                            ?.name ??
                                                                            '—'}
                                                                    </TableCell>
                                                                    <TableCell className="text-right">
                                                                        {formatMoney(
                                                                            payment.amount,
                                                                            currency,
                                                                        )}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ),
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        )}
                                    </div>

                                    <div className="ml-auto max-w-xs space-y-1.5 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">
                                                Total Projects
                                            </span>
                                            <span className="font-medium">
                                                {formatMoney(
                                                    line.projects_total,
                                                    currency,
                                                )}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">
                                                Total Paid
                                            </span>
                                            <span className="font-medium">
                                                {formatMoney(
                                                    line.payments_total,
                                                    currency,
                                                )}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-t pt-1.5 font-semibold">
                                            <span>
                                                {line.credit !== 0
                                                    ? 'Credit'
                                                    : 'Outstanding'}
                                            </span>
                                            <span>
                                                {formatMoney(
                                                    line.credit !== 0
                                                        ? line.credit
                                                        : line.outstanding,
                                                    currency,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    },
                )}

                <p className="text-xs text-muted-foreground">
                    Statement generated on {formatDate(statement.generatedAt)}.
                </p>
            </div>
        </>
    );
}

ClientStatement.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clients', href: '/clients' },
        { title: props.client.name, href: `/clients/${props.client.id}` },
        { title: 'Statement', href: `/clients/${props.client.id}/statement` },
    ],
});
