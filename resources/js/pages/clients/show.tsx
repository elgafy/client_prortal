import { Form, Head } from '@inertiajs/react';
import { Link, router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/heading';
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
import { formatMoney } from '@/lib/format';
import { edit as clientsEdit, show as clientsShow } from '@/routes/clients';
import { show as projectsShow } from '@/routes/projects';
import type { AccountSummary, Client, Project } from '@/types';

type PageProps = {
    client: Client;
    projects: Project[];
    summary: AccountSummary;
};

const projectStatusVariant: Record<
    Project['status'],
    'secondary' | 'outline' | 'destructive'
> = {
    active: 'secondary',
    completed: 'outline',
    cancelled: 'destructive',
};

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value ?? '—'}</span>
        </div>
    );
}

export default function ShowClient({ client, projects, summary }: PageProps) {
    return (
        <>
            <Head title={client.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="space-y-0.5">
                        <h2 className="text-xl font-semibold tracking-tight">
                            {client.name}
                            <Badge
                                variant={
                                    client.status === 'active'
                                        ? 'secondary'
                                        : 'outline'
                                }
                                className="ml-3 align-middle"
                            >
                                {client.status}
                            </Badge>
                        </h2>
                        {client.company_name && (
                            <p className="text-sm text-muted-foreground">
                                {client.company_name}
                            </p>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <Form {...ClientController.archive.form(client.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    name="status"
                                    value={
                                        client.status === 'active'
                                            ? 'archived'
                                            : 'active'
                                    }
                                >
                                    {client.status === 'active'
                                        ? 'Archive'
                                        : 'Restore'}
                                </Button>
                            )}
                        </Form>
                        <Button asChild>
                            <Link href={clientsEdit(client.id)}>Edit</Link>
                        </Button>
                    </div>
                </div>

                {summary.has_multiple_currencies && (
                    <div className="flex items-center gap-2 rounded-xl border border-brand-ochre/50 bg-brand-ochre/10 p-3 text-sm text-foreground">
                        <TriangleAlert className="size-4 shrink-0 text-brand-ochre" />
                        This account uses multiple currencies. Amounts are shown
                        per currency and are never combined.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    {Object.entries(summary.currencies).map(
                        ([currency, line]) => (
                            <Card key={currency}>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {line.credit !== '0.0000'
                                            ? 'Credit'
                                            : 'Outstanding'}
                                        {' · '}
                                        {currency}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-display">
                                        {line.credit !== '0.0000'
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
                                        − Payments{' '}
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

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Client Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <InfoRow label="Name" value={client.name} />
                            <Separator />
                            <InfoRow
                                label="Company"
                                value={client.company_name}
                            />
                            <Separator />
                            <InfoRow label="Email" value={client.email} />
                            <Separator />
                            <InfoRow label="Mobile" value={client.mobile} />
                            <Separator />
                            <InfoRow
                                label="Default Currency"
                                value={client.currency}
                            />
                            {client.address && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Address"
                                        value={client.address}
                                    />
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-6 lg:col-span-2">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Projects"
                                description="What the client owes"
                            />
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/projects/create?client=${client.id}`}
                                >
                                    New Project
                                </Link>
                            </Button>
                        </div>

                        {projects.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                                No projects yet.
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Project</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {projects.map((project) => (
                                            <TableRow
                                                key={project.id}
                                                className="cursor-pointer"
                                                onClick={() =>
                                                    router.visit(
                                                        projectsShow(
                                                            project.id,
                                                        ),
                                                    )
                                                }
                                            >
                                                <TableCell className="font-medium">
                                                    {project.name}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        project.amount,
                                                        project.currency,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            projectStatusVariant[
                                                                project.status
                                                            ]
                                                        }
                                                    >
                                                        {project.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        <Heading
                            variant="small"
                            title="Payments"
                            description="What the client has paid"
                        />
                        <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                            No payments yet.
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

ShowClient.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clients', href: '/clients' },
        { title: props.client.name, href: `/clients/${props.client.id}` },
    ],
});
