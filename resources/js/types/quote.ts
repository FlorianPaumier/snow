export type Quote = {
    id: string;
    organization_id: string;
    client_id: string;
    source_contract_id: string | null;
    renewal_reviewed_at: string | null;
    contract_id: string | null;
    season: string;
    status: 'draft' | 'sent' | 'accepted' | 'replaced' | 'declined';
    services: { label: string; amount: number }[];
    payments: Record<string, { amount: number; dueDate: string | null }>;
    customer: { name: string; address: string; email: string } | null;
    organization_name: string | null;
    expires_at: string | null;
    accepted_at: string | null;
};
