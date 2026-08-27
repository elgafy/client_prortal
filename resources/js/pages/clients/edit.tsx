import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClientFields from '@/components/clients/client-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Client } from '@/types';

type PageProps = {
    client: Client;
    currencies: string[];
};

export default function EditClient({ client, currencies }: PageProps) {
    return (
        <>
            <Head title={`Edit ${client.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title="Edit Client" description={client.name} />

                <Form
                    {...ClientController.update.form(client.id)}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <ClientFields
                                currencies={currencies}
                                errors={errors}
                                defaults={client}
                            />

                            <div className="grid max-w-sm gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={client.status}
                                >
                                    <SelectTrigger
                                        id="status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="archived">
                                            Archived
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={`/clients/${client.id}`}>
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

EditClient.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clients', href: '/clients' },
        { title: props.client.name, href: `/clients/${props.client.id}` },
        { title: 'Edit', href: `/clients/${props.client.id}/edit` },
    ],
});
