export type ClientStatus = 'active' | 'archived';

export type Client = {
    id: number;
    name: string;
    company_name: string | null;
    email: string | null;
    mobile: string | null;
    address: string | null;
    currency: string;
    status: ClientStatus;
    created_at: string;
    updated_at: string;
};

export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
};
