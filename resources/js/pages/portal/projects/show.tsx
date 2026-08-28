import { Head, usePage } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import CommentThread from '@/components/comments/comment-thread';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
import type { Comment, Payment, Project } from '@/types';

type PageProps = {
    project: Project;
    balance: number;
    payments: Payment[];
    comments: Comment[];
};

export default function PortalProjectShow() {
    const { project, balance, payments, comments } = usePage<PageProps>().props;

    return (
        <>
            <Head title={project.name} />

            <div className="flex h-full flex-1 flex-col gap-4">
                <div className="space-y-0.5">
                    <h2 className="text-xl font-semibold tracking-tight">
                        {project.name}
                        <Badge
                            variant="secondary"
                            className="ml-3 align-middle"
                        >
                            {project.status}
                        </Badge>
                    </h2>
                    {project.description && (
                        <p className="text-sm text-muted-foreground">
                            {project.description}
                        </p>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Project Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-muted-foreground">
                                    Description
                                </span>
                                <span className="max-w-[60%] text-right font-medium">
                                    {project.description ?? '—'}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-muted-foreground">
                                    Amount
                                </span>
                                <span className="text-right font-medium">
                                    {formatMoney(
                                        project.amount,
                                        project.currency,
                                    )}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-muted-foreground">
                                    Balance
                                </span>
                                <span className="text-right font-medium">
                                    {formatMoney(balance, project.currency)}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex items-start justify-between gap-4 text-sm">
                                <span className="text-muted-foreground">
                                    Date
                                </span>
                                <span className="text-right font-medium">
                                    {formatDate(project.project_date)}
                                </span>
                            </div>
                            {project.link && (
                                <>
                                    <Separator />
                                    <div className="flex items-start justify-between gap-4 text-sm">
                                        <span className="text-muted-foreground">
                                            Link
                                        </span>
                                        <a
                                            href={project.link}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 font-medium hover:underline"
                                        >
                                            View Project
                                            <ExternalLink className="size-3.5" />
                                        </a>
                                    </div>
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
                            <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
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
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        <Heading variant="small" title="Comments" />
                        <CommentThread
                            comments={comments}
                            commentableType="project"
                            commentableId={project.id}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
