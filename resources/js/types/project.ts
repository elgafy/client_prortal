export type ProjectStatus = 'active' | 'completed' | 'cancelled';

export type Project = {
    id: number;
    client_id: number;
    name: string;
    description: string | null;
    amount: number;
    currency: string;
    project_date: string | null;
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
