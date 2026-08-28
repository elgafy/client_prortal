import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import type { Payment, ProjectOption } from '@/types';

type PageProps = {
    payment: Payment;
    projects: ProjectOption[];
    methods: string[];
};

export default function EditPayment({ payment, projects, methods }: PageProps) {
    return (
        <>
            <Head title={`Edit Payment #${payment.id}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Edit Payment"
                    description={`${payment.client?.name ?? ''} · ${formatDate(payment.payment_date)}`}
                />

                <Form
                    {...PaymentController.update.form(payment.id)}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="project_id">Project</Label>
                                <Select
                                    name="project_id"
                                    defaultValue={
                                        payment.project_id?.toString() ?? 'none'
                                    }
                                >
                                    <SelectTrigger
                                        id="project_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Account payment" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Account payment (no project)
                                        </SelectItem>
                                        {projects.map((project) => (
                                            <SelectItem
                                                key={project.id}
                                                value={project.id.toString()}
                                            >
                                                {project.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.project_id} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Amount</Label>
                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        step="1"
                                        min="1"
                                        required
                                        defaultValue={payment.amount}
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        name="currency"
                                        defaultValue={payment.currency}
                                    >
                                        <SelectTrigger
                                            id="currency"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {/* Distinct currencies across the payment's client projects + the payment itself */}
                                            {[
                                                ...new Set([
                                                    payment.currency,
                                                    ...projects.map(
                                                        (p) => p.currency,
                                                    ),
                                                ]),
                                            ].map((code) => (
                                                <SelectItem
                                                    key={code}
                                                    value={code}
                                                >
                                                    {code}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="payment_date">Date</Label>
                                    <Input
                                        id="payment_date"
                                        name="payment_date"
                                        type="date"
                                        required
                                        defaultValue={payment.payment_date}
                                    />
                                    <InputError message={errors.payment_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="method">Method</Label>
                                    <Select
                                        name="method"
                                        defaultValue={payment.method}
                                    >
                                        <SelectTrigger
                                            id="method"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Method" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {methods.map((method) => (
                                                <SelectItem
                                                    key={method}
                                                    value={method}
                                                >
                                                    {method}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.method} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="received_from">
                                        Received from
                                    </Label>
                                    <Input
                                        id="received_from"
                                        name="received_from"
                                        defaultValue={
                                            payment.received_from ?? undefined
                                        }
                                    />
                                    <InputError
                                        message={errors.received_from}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="received_by">
                                        Received by
                                    </Label>
                                    <Input
                                        id="received_by"
                                        name="received_by"
                                        defaultValue={
                                            payment.received_by ?? undefined
                                        }
                                    />
                                    <InputError message={errors.received_by} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="note">Note</Label>
                                <Textarea
                                    id="note"
                                    name="note"
                                    rows={2}
                                    defaultValue={payment.note ?? undefined}
                                />
                                <InputError message={errors.note} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={`/payments/${payment.id}`}>
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditPayment.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Payments', href: '/payments' },
        {
            title: `Payment #${props.payment.id}`,
            href: `/payments/${props.payment.id}`,
        },
        { title: 'Edit', href: `/payments/${props.payment.id}/edit` },
    ],
});
