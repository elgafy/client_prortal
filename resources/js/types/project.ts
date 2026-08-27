export type ProjectStatus = 'active' | 'completed' | 'cancelled';

export type Project = {
    id: number;
    client_id: number;
    name: string;
    description: string | null;
    amount: string;
    currency: string;
    status: ProjectStatus;
    link: string | null;
    created_at: string;
    updated_at: string;
    client?: {
        id: number;
        name: string;
    };
};

export type AccountSummaryLine = {
    projects_total: string;
    payments_total: string;
    net: string;
    outstanding: string;
    credit: string;
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
