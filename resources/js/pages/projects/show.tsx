import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ExternalLink, Link2 } from 'lucide-react';
import Heading from '@/components/heading';
import DiscountList from '@/components/projects/discount-list';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import { edit as projectsEdit } from '@/routes/projects';
import type { Payment, Project } from '@/types';

type PageProps = {
    project: Project;
    balance: number;
    payments: Payment[];
};

const statusVariant: Record<
    Project['status'],
    'secondary' | 'outline' | 'destructive'
> = {
    active: 'secondary',
    completed: 'outline',
    cancelled: 'destructive',
};

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value ?? '—'}</span>
        </div>
    );
}

export default function ShowProject({ project, balance, payments }: PageProps) {
    return (
        <>
            <Head title={project.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="space-y-0.5">
                        <h2 className="text-xl font-semibold tracking-tight">
                            {project.name}
                            <Badge
                                variant={statusVariant[project.status]}
                                className="ml-3 align-middle"
                            >
                                {project.status}
                            </Badge>
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {project.client?.name}
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={projectsEdit(project.id)}>Edit</Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Project Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <InfoRow
                                label="Subtotal"
                                value={
                                    <span className="text-muted-foreground">
                                        {formatMoney(
                                            project.subtotal,
                                            project.currency,
                                        )}
                                    </span>
                                }
                            />
                            {project.discount_total > 0 && (
                                <>
                                    <Separator />
                                    <div>
                                        <InfoRow
                                            label="Discount"
                                            value={`−${formatMoney(project.discount_total, project.currency)}`}
                                        />
                                        <DiscountList
                                            discounts={project.discounts ?? []}
                                            currency={project.currency}
                                        />
                                    </div>
                                </>
                            )}
                            <Separator />
                            <InfoRow
                                label="Amount"
                                value={formatMoney(
                                    project.amount,
                                    project.currency,
                                )}
                            />
                            <Separator />
                            <InfoRow
                                label="Balance"
                                value={formatMoney(balance, project.currency)}
                            />
                            <Separator />
                            <InfoRow
                                label="Date"
                                value={formatDate(project.project_date)}
                            />
                            <Separator />
                            <InfoRow
                                label="Client"
                                value={
                                    <Link
                                        href={`/clients/${project.client_id}`}
                                        className="hover:underline"
                                    >
                                        {project.client?.name}
                                    </Link>
                                }
                            />
                            {project.description && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Description"
                                        value={project.description}
                                    />
                                </>
                            )}
                            {project.link && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Link"
                                        value={
                                            <a
                                                href={project.link}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1 hover:underline"
                                            >
                                                View Project
                                                <ExternalLink className="size-3.5" />
                                            </a>
                                        }
                                    />
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-6 lg:col-span-2">
                        <Heading
                            variant="small"
                            title="Payments"
                            description="Payments assigned to this project"
                        />

                        {payments.length === 0 ? (
                            <div className="flex items-center gap-3 rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                                <Link2 className="size-4 shrink-0" />
                                No payments assigned yet.
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>
                                                    {formatDate(
                                                        payment.payment_date,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        payment.amount,
                                                        payment.currency,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {payment.method}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {payment.status}
                                                    </Badge>
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

ShowProject.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/projects' },
        { title: props.project.name, href: `/projects/${props.project.id}` },
    ],
});
