import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
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
import { index as projectsIndex } from '@/routes/projects';
import type { ClientOption } from '@/types';

type PageProps = {
    clients: ClientOption[];
    currencies: string[];
    selectedClientId: number | null;
};

export default function CreateProject({
    clients,
    currencies,
    selectedClientId,
}: PageProps) {
    const initialClient = clients.find((c) => c.id === selectedClientId);

    const [clientId, setClientId] = useState<string | undefined>(
        initialClient?.id.toString(),
    );
    const [currency, setCurrency] = useState<string | undefined>(
        initialClient?.currency ?? currencies[0],
    );

    // Selecting a client switches the project currency to the client's default.
    const handleClientChange = (value: string) => {
        setClientId(value);

        const client = clients.find((c) => c.id.toString() === value);

        if (client) {
            setCurrency(client.currency);
        }
    };

    return (
        <>
            <Head title="New Project" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New Project"
                    description="Add work for a client"
                />

                <Form
                    {...ProjectController.store.form()}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
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
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="Project name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Amount</Label>
                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        required
                                        placeholder="0.0000"
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
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    placeholder="Description (optional)"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="link">Link</Label>
                                <Input
                                    id="link"
                                    name="link"
                                    type="url"
                                    placeholder="https://example.com/project (optional)"
                                />
                                <InputError message={errors.link} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create Project
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={projectsIndex()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateProject.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/projects' },
        { title: 'New Project', href: '/projects/create' },
    ],
};
