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
import {
    index as clientsIndex,
    create as clientsCreate,
    show as clientsShow,
} from '@/routes/clients';
import type { Client, Paginator } from '@/types';

type PageProps = {
    clients: Paginator<Client>;
    filters: { search?: string };
};

export default function ClientsIndex() {
    const { clients, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search === (filters.search ?? '')) {
                return;
            }

            router.get(
                clientsIndex.url(),
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
            <Head title="Clients" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Clients"
                        description="Manage client accounts"
                    />
                    <Button asChild>
                        <Link href={clientsCreate()}>
                            <Plus />
                            New Client
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search name, company, email, mobile…"
                        className="pl-9"
                    />
                </div>

                {clients.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        {filters.search
                            ? 'No clients match your search.'
                            : 'No clients yet. Create your first client to get started.'}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {clients.data.map((client) => (
                                    <TableRow
                                        key={client.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.visit(clientsShow(client.id))
                                        }
                                    >
                                        <TableCell className="font-medium">
                                            {client.name}
                                        </TableCell>
                                        <TableCell>
                                            {client.company_name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {client.email ?? '—'}
                                        </TableCell>
                                        <TableCell>{client.currency}</TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    client.status === 'active'
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {client.status}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {clients.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Showing {clients.from}–{clients.to} of{' '}
                            {clients.total}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={clients.current_page <= 1}
                                onClick={() =>
                                    router.visit(
                                        clientsIndex.url({
                                            query: {
                                                page: clients.current_page - 1,
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
                                    clients.current_page >= clients.last_page
                                }
                                onClick={() =>
                                    router.visit(
                                        clientsIndex.url({
                                            query: {
                                                page: clients.current_page + 1,
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

ClientsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clients', href: '/clients' },
    ],
};
