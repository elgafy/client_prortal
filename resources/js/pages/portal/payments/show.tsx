import { Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import CommentThread from '@/components/comments/comment-thread';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatDate, formatMoney } from '@/lib/format';
import type { Comment, Payment } from '@/types';

type PageProps = {
    payment: Payment;
    comments: Comment[];
};

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value ?? '—'}</span>
        </div>
    );
}

export default function PortalPaymentShow() {
    const { payment, comments } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Payment Details" />

            <div className="flex h-full flex-1 flex-col gap-4">
                <div className="space-y-0.5">
                    <h2 className="text-xl font-semibold tracking-tight">
                        {formatMoney(payment.amount, payment.currency)}
                        <Badge
                            variant={
                                payment.status === 'active'
                                    ? 'secondary'
                                    : 'destructive'
                            }
                            className="ml-3 align-middle"
                        >
                            {payment.status}
                        </Badge>
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {formatDate(payment.payment_date)}
                    </p>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Payment Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <InfoRow
                            label="Project"
                            value={
                                payment.project ? (
                                    <Link
                                        href={`/portal/projects/${payment.project.id}`}
                                        className="hover:underline"
                                    >
                                        {payment.project.name}
                                    </Link>
                                ) : (
                                    'Account payment'
                                )
                            }
                        />
                        <Separator />
                        <InfoRow label="Method" value={payment.method} />
                        <Separator />
                        <InfoRow
                            label="Date"
                            value={formatDate(payment.payment_date)}
                        />
                    </CardContent>
                </Card>

                <div className="max-w-2xl space-y-3">
                    <Heading variant="small" title="Comments" />
                    <CommentThread
                        comments={comments}
                        commentableType="payment"
                        commentableId={payment.id}
                    />
                </div>
            </div>
        </>
    );
}
