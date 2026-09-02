import { Badge } from '@/components/ui/badge';
import { formatMoney, formatPercent } from '@/lib/format';
import type { ProjectDiscount } from '@/types';

type DiscountListProps = {
    discounts: ProjectDiscount[];
    currency: string;
};

/**
 * Itemized discount/deduction rows under the project's discount total.
 */
export default function DiscountList({
    discounts,
    currency,
}: DiscountListProps) {
    if (discounts.length === 0) {
        return null;
    }

    return (
        <ul className="mt-2 space-y-1.5">
            {discounts.map((discount) => (
                <li
                    key={discount.id}
                    className="flex items-center justify-between gap-3 text-sm"
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <Badge
                            variant={
                                discount.type === 'deduction'
                                    ? 'destructive'
                                    : 'secondary'
                            }
                            className="shrink-0 capitalize"
                        >
                            {discount.type}
                        </Badge>
                        <span className="truncate text-muted-foreground">
                            {discount.title}
                        </span>
                        {discount.mode === 'percentage' && (
                            <span className="shrink-0 text-xs text-muted-foreground">
                                {formatPercent(discount.percentage)}
                            </span>
                        )}
                    </span>
                    <span className="shrink-0 font-medium">
                        −{formatMoney(discount.value, currency)}
                    </span>
                </li>
            ))}
        </ul>
    );
}
