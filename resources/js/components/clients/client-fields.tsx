import InputError from '@/components/input-error';
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

type Props = {
    currencies: string[];
    errors: Record<string, string>;
    defaults?: {
        name?: string;
        company_name?: string | null;
        email?: string | null;
        mobile?: string | null;
        address?: string | null;
        currency?: string;
    };
};

export default function ClientFields({ currencies, errors, defaults }: Props) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    defaultValue={defaults?.name}
                    placeholder="Client name"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="company_name">Company</Label>
                <Input
                    id="company_name"
                    name="company_name"
                    defaultValue={defaults?.company_name ?? undefined}
                    placeholder="Company name (optional)"
                />
                <InputError message={errors.company_name} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        defaultValue={defaults?.email ?? undefined}
                        placeholder="Email (optional)"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mobile">Mobile</Label>
                    <Input
                        id="mobile"
                        name="mobile"
                        defaultValue={defaults?.mobile ?? undefined}
                        placeholder="Mobile (optional)"
                    />
                    <InputError message={errors.mobile} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="currency">Default currency</Label>
                    <Select name="currency" defaultValue={defaults?.currency}>
                        <SelectTrigger id="currency" className="w-full">
                            <SelectValue placeholder="Select currency" />
                        </SelectTrigger>
                        <SelectContent>
                            {currencies.map((code) => (
                                <SelectItem key={code} value={code}>
                                    {code}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.currency} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="address">Address</Label>
                <Textarea
                    id="address"
                    name="address"
                    rows={3}
                    defaultValue={defaults?.address ?? undefined}
                    placeholder="Address (optional)"
                />
                <InputError message={errors.address} />
            </div>
        </>
    );
}
