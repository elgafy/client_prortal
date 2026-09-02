import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import type { Project } from '@/types';

type PageProps = {
    projects: Project[];
};

export default function PortalProjects() {
    const { projects } = usePage<PageProps>().props;

    return (
        <>
            <Head title="My Projects" />

            <div className="flex h-full flex-1 flex-col gap-4">
                <Heading
                    title="My Projects"
                    description="Work billed to your account"
                />

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
                                    <TableHead>Date</TableHead>
                                    <TableHead>Subtotal</TableHead>
                                    <TableHead>Discount</TableHead>
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
                                                `/portal/projects/${project.id}`,
                                            )
                                        }
                                    >
                                        <TableCell className="font-medium">
                                            {project.name}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(project.project_date)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatMoney(
                                                project.subtotal,
                                                project.currency,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {project.discount_total > 0
                                                ? `−${formatMoney(project.discount_total, project.currency)}`
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                project.amount,
                                                project.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {project.status}
                                            </Badge>
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
