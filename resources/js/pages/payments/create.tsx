import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
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
import { index as paymentsIndex } from '@/routes/payments';
import type { ClientOption, ProjectOption } from '@/types';

type PageProps = {
    clients: ClientOption[];
    projects: ProjectOption[];
    methods: string[];
    currencies: string[];
    selectedClientId: number | null;
};

export default function CreatePayment({
    clients,
    projects,
    methods,
    currencies,
    selectedClientId,
}: PageProps) {
    const initialClient = clients.find((c) => c.id === selectedClientId);

    const [clientId, setClientId] = useState<string | undefined>(
        initialClient?.id.toString(),
    );
    const [projectId, setProjectId] = useState<string>('none');
    const [currency, setCurrency] = useState<string | undefined>(
        initialClient?.currency ?? currencies[0],
    );

    const clientProjects = projects.filter(
        (p) => p.client_id.toString() === clientId,
    );

    // Selecting a client switches the currency to the client's default and
    // resets any project assignment.
    const handleClientChange = (value: string) => {
        setClientId(value);
        setProjectId('none');

        const client = clients.find((c) => c.id.toString() === value);

        if (client) {
            setCurrency(client.currency);
        }
    };

    // Assigning a project pins the currency to the project's currency.
    const handleProjectChange = (value: string) => {
        setProjectId(value);

        const project = clientProjects.find((p) => p.id.toString() === value);

        if (project) {
            setCurrency(project.currency);
        }
    };

    return (
        <>
            <Head title="New Payment" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New Payment"
                    description="Record a client payment"
                />

                <Form
                    {...PaymentController.store.form()}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="client_id">Client</Label>
                                    <Select
                                        name="client_id"
                                        value={clientId}
                                        onValueChange={handleClientChange}
                                    >
                                        <SelectTrigger
                                            id="client_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select client" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem
                                                    key={client.id}
                                                    value={client.id.toString()}
                                                >
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.client_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="project_id">Project</Label>
                                    <Select
                                        name="project_id"
                                        value={projectId}
                                        onValueChange={handleProjectChange}
                                        disabled={!clientId}
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
                                            {clientProjects.map((project) => (
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
                                        placeholder="0"
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        name="currency"
                                        value={currency}
                                        onValueChange={setCurrency}
                                    >
                                        <SelectTrigger
                                            id="currency"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((code) => (
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
                                        defaultValue={new Date()
                                            .toISOString()
                                            .slice(0, 10)}
                                    />
                                    <InputError message={errors.payment_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="method">Method</Label>
                                    <Select
                                        name="method"
                                        defaultValue={methods[0]}
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
                                        placeholder="Optional"
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
                                        placeholder="Optional"
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
                                    placeholder="Optional note"
                                />
                                <InputError message={errors.note} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Record Payment
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={paymentsIndex()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreatePayment.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Payments', href: '/payments' },
        { title: 'New Payment', href: '/payments/create' },
    ],
};
