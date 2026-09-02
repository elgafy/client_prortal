import { Plus, Trash2 } from 'lucide-react';
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
import type { ProjectDiscount } from '@/types';

/** Editable discount row — values are strings so inputs stay controlled. */
export type DiscountRow = {
    title: string;
    type: ProjectDiscount['type'];
    mode: ProjectDiscount['mode'];
    amount: string;
    percentage: string;
    description: string;
};

export function emptyDiscountRow(): DiscountRow {
    return {
        title: '',
        type: 'discount',
        mode: 'amount',
        amount: '',
        percentage: '',
        description: '',
    };
}

export function discountRowsFrom(project: ProjectDiscount[]): DiscountRow[] {
    return project.map((discount) => ({
        title: discount.title,
        type: discount.type,
        mode: discount.mode,
        amount: discount.amount !== null ? String(discount.amount) : '',
        percentage: discount.percentage ?? '',
        description: discount.description ?? '',
    }));
}

type DiscountFieldsProps = {
    rows: DiscountRow[];
    onChange: (rows: DiscountRow[]) => void;
    errors: Partial<Record<string, string>>;
};

/**
 * Repeatable discount/deduction rows for the project form. Inputs use
 * bracket names (`discounts[0][title]`) which Inertia submits as a nested
 * array; the backend validates and derives the final amount.
 */
export default function DiscountFields({
    rows,
    onChange,
    errors,
}: DiscountFieldsProps) {
    const update = (index: number, patch: Partial<DiscountRow>) => {
        onChange(
            rows.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );
    };

    const remove = (index: number) => {
        onChange(rows.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-3">
            {rows.map((row, index) => {
                const prefix = `discounts.${index}`;
                const name = `discounts[${index}]`;

                return (
                    <div
                        key={index}
                        className="space-y-3 rounded-xl border bg-muted/30 p-4"
                    >
                        <div className="grid gap-3 sm:grid-cols-[1fr_auto_auto_9rem_auto]">
                            <div className="grid gap-1.5">
                                <Label htmlFor={`${prefix}.title`}>Title</Label>
                                <Input
                                    id={`${prefix}.title`}
                                    name={`${name}[title]`}
                                    value={row.title}
                                    onChange={(e) =>
                                        update(index, { title: e.target.value })
                                    }
                                    placeholder="e.g. Early payment"
                                />
                                <InputError
                                    message={errors[`${prefix}.title`]}
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`${prefix}.type`}>Type</Label>
                                <Select
                                    name={`${name}[type]`}
                                    value={row.type}
                                    onValueChange={(value) =>
                                        update(index, {
                                            type: value as DiscountRow['type'],
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        id={`${prefix}.type`}
                                        className="w-32"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="discount">
                                            Discount
                                        </SelectItem>
                                        <SelectItem value="deduction">
                                            Deduction
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors[`${prefix}.type`]}
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`${prefix}.mode`}>
                                    Calculation
                                </Label>
                                <Select
                                    name={`${name}[mode]`}
                                    value={row.mode}
                                    onValueChange={(value) =>
                                        update(index, {
                                            mode: value as DiscountRow['mode'],
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        id={`${prefix}.mode`}
                                        className="w-32"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="amount">
                                            Amount
                                        </SelectItem>
                                        <SelectItem value="percentage">
                                            Percentage
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors[`${prefix}.mode`]}
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`${prefix}.value`}>
                                    {row.mode === 'percentage'
                                        ? 'Percent'
                                        : 'Value'}
                                </Label>
                                {row.mode === 'percentage' ? (
                                    <Input
                                        id={`${prefix}.value`}
                                        name={`${name}[percentage]`}
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max="100"
                                        required
                                        value={row.percentage}
                                        onChange={(e) =>
                                            update(index, {
                                                percentage: e.target.value,
                                            })
                                        }
                                        placeholder="0.00"
                                    />
                                ) : (
                                    <Input
                                        id={`${prefix}.value`}
                                        name={`${name}[amount]`}
                                        type="number"
                                        step="1"
                                        min="1"
                                        required
                                        value={row.amount}
                                        onChange={(e) =>
                                            update(index, {
                                                amount: e.target.value,
                                            })
                                        }
                                        placeholder="0"
                                    />
                                )}
                                <InputError
                                    message={
                                        row.mode === 'percentage'
                                            ? errors[`${prefix}.percentage`]
                                            : errors[`${prefix}.amount`]
                                    }
                                />
                            </div>

                            <div className="flex items-end pb-0.5">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove discount"
                                    onClick={() => remove(index)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor={`${prefix}.description`}>
                                Description{' '}
                                <span className="font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id={`${prefix}.description`}
                                name={`${name}[description]`}
                                value={row.description}
                                onChange={(e) =>
                                    update(index, {
                                        description: e.target.value,
                                    })
                                }
                            />
                            <InputError
                                message={errors[`${prefix}.description`]}
                            />
                        </div>
                    </div>
                );
            })}

            <InputError message={errors.discounts} />

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => onChange([...rows, emptyDiscountRow()])}
            >
                <Plus />
                Add discount
            </Button>
        </div>
    );
}
