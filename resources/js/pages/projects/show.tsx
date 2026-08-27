import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ExternalLink, Link2 } from 'lucide-react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatMoney } from '@/lib/format';
import { edit as projectsEdit } from '@/routes/projects';
import type {Project} from '@/types';

type PageProps = {
    project: Project;
    balance: string;
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

export default function ShowProject({ project, balance }: PageProps) {
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
                        <div className="flex items-center gap-3 rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                            <Link2 className="size-4 shrink-0" />
                            No payments assigned yet.
                        </div>
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
