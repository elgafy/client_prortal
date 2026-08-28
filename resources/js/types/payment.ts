export type PaymentStatus = 'active' | 'void';

export type Payment = {
    id: number;
    client_id: number;
    project_id: number | null;
    amount: number;
    currency: string;
    payment_date: string;
    method: string;
    received_from: string | null;
    received_by: string | null;
    note: string | null;
    status: PaymentStatus;
    created_at: string;
    updated_at: string;
    client?: {
        id: number;
        name: string;
    };
    project?: {
        id: number;
        name: string;
    } | null;
};

export type ProjectOption = {
    id: number;
    name: string;
    client_id: number;
    currency: string;
};

export type Comment = {
    id: number;
    user_id: number;
    commentable_id: number;
    commentable_type: string;
    body: string;
    is_internal: boolean;
    created_at: string;
    user?: {
        id: number;
        name: string;
    };
};
