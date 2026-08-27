import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
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
import type { Project } from '@/types';

type PageProps = {
    project: Project;
    currencies: string[];
};

export default function EditProject({ project, currencies }: PageProps) {
    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title="Edit Project" description={project.name} />

                <Form
                    {...ProjectController.update.form(project.id)}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={project.name}
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
                                        defaultValue={project.amount}
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        name="currency"
                                        defaultValue={project.currency}
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

                            <div className="grid max-w-sm gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={project.status}
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
                                        <SelectItem value="completed">
                                            Completed
                                        </SelectItem>
                                        <SelectItem value="cancelled">
                                            Cancelled
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    defaultValue={
                                        project.description ?? undefined
                                    }
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="link">Link</Label>
                                <Input
                                    id="link"
                                    name="link"
                                    type="url"
                                    defaultValue={project.link ?? undefined}
                                    placeholder="https://example.com/project (optional)"
                                />
                                <InputError message={errors.link} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link href={`/projects/${project.id}`}>
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

EditProject.layout = (props: PageProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/projects' },
        { title: props.project.name, href: `/projects/${props.project.id}` },
        { title: 'Edit', href: `/projects/${props.project.id}/edit` },
    ],
});
