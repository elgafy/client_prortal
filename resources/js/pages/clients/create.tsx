import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClientFields from '@/components/clients/client-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as clientsIndex } from '@/routes/clients';

type PageProps = {
    currencies: string[];
};

export default function CreateClient({ currencies }: PageProps) {
    return (
        <>
            <Head title="New Client" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New Client"
                    description="Add a client account"
                />

                <Form
                    {...ClientController.store.form()}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <ClientFields
                                currencies={currencies}
                                errors={errors}
                            />

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create Client
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={clientsIndex()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateClient.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clients', href: '/clients' },
        { title: 'New Client', href: '/clients/create' },
    ],
};
