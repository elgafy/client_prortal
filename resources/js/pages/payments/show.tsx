import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import CommentThread from '@/components/comments/comment-thread';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatDate, formatMoney } from '@/lib/format';
import { edit as paymentsEdit } from '@/routes/payments';
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

export default function ShowPayment({ payment, comments }: PageProps) {
    return (
        <>
            <Head title={`Payment · ${payment.client?.name ?? ''}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
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
                            {payment.client?.name} ·{' '}
                            {formatDate(payment.payment_date)}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        {payment.status === 'active' && (
                            <Form {...PaymentController.void.form(payment.id)}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Void
                                    </Button>
                                )}
                            </Form>
                        )}
                        <Button asChild>
                            <Link href={paymentsEdit(payment.id)}>Edit</Link>
                        </Button>
                    </div>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Payment Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <InfoRow
                            label="Client"
                            value={
                                <Link
                                    href={`/clients/${payment.client_id}`}
                                    className="hover:underline"
                                >
                                    {payment.client?.name}
                                </Link>
                            }
                        />
                        <Separator />
                        <InfoRow
                            label="Project"
                            value={
                                payment.project ? (
                                    <Link
                                        href={`/projects/${payment.project.id}`}
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
                        <Separator />
                        <InfoRow
                            label="Received from"
                            value={payment.received_from}
                        />
                        <Separator />
                        <InfoRow
                            label="Received by"
                            value={payment.received_by}
                        />
                        {payment.note && (
                            <>
                                <Separator />
                                <InfoRow label="Note" value={payment.note} />
                            </>
                        )}
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

ShowPayment.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Payments', href: '/payments' },
        {
            title: `Payment #${props.payment.id}`,
            href: `/payments/${props.payment.id}`,
        },
    ],
});
