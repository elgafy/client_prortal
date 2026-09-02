export type ProjectStatus = 'active' | 'completed' | 'cancelled';

export type ProjectDiscount = {
    id: number;
    project_id: number;
    type: 'discount' | 'deduction';
    mode: 'amount' | 'percentage';
    amount: number | null;
    percentage: string | null;
    title: string;
    description: string | null;
    /** Computed value in whole currency units. */
    value: number;
};

export type Project = {
    id: number;
    client_id: number;
    name: string;
    description: string | null;
    subtotal: number;
    discount_total: number;
    amount: number;
    currency: string;
    project_date: string | null;
    status: ProjectStatus;
    link: string | null;
    created_at: string;
    updated_at: string;
    discounts?: ProjectDiscount[];
    client?: {
        id: number;
        name: string;
    };
};

export type AccountSummaryLine = {
    projects_total: number;
    payments_total: number;
    net: number;
    outstanding: number;
    credit: number;
};

export type AccountSummary = {
    currencies: Record<string, AccountSummaryLine>;
    has_multiple_currencies: boolean;
};

export type ClientOption = {
    id: number;
    name: string;
    currency: string;
};
