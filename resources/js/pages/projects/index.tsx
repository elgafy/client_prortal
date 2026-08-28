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
    create as projectsCreate,
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';
import type { Paginator, Project } from '@/types';

type PageProps = {
    projects: Paginator<Project>;
    filters: { search?: string };
};

const statusVariant: Record<
    Project['status'],
    'secondary' | 'outline' | 'destructive'
> = {
    active: 'secondary',
    completed: 'outline',
    cancelled: 'destructive',
};

export default function ProjectsIndex() {
    const { projects, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search === (filters.search ?? '')) {
                return;
            }

            router.get(
                projectsIndex.url(),
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
            <Head title="Projects" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Projects"
                        description="Work billed to clients"
                    />
                    <Button asChild>
                        <Link href={projectsCreate()}>
                            <Plus />
                            New Project
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search project or client…"
                        className="pl-9"
                    />
                </div>

                {projects.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        {filters.search
                            ? 'No projects match your search.'
                            : 'No projects yet. Create your first project to get started.'}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Project</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {projects.data.map((project) => (
                                    <TableRow
                                        key={project.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.visit(
                                                projectsShow(project.id),
                                            )
                                        }
                                    >
                                        <TableCell className="font-medium">
                                            {project.name}
                                        </TableCell>
                                        <TableCell>
                                            {project.client?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(project.project_date)}
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
                                                    statusVariant[
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

                {projects.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Showing {projects.from}–{projects.to} of{' '}
                            {projects.total}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={projects.current_page <= 1}
                                onClick={() =>
                                    router.visit(
                                        projectsIndex.url({
                                            query: {
                                                page: projects.current_page - 1,
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
                                    projects.current_page >= projects.last_page
                                }
                                onClick={() =>
                                    router.visit(
                                        projectsIndex.url({
                                            query: {
                                                page: projects.current_page + 1,
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

ProjectsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/projects' },
    ],
};
