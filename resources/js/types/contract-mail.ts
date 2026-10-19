export type ContractMail = {
    status:
        | 'queued'
        | 'sending'
        | 'sent'
        | 'failed'
        | 'uncertain'
        | 'skipped'
        | null;
    sentAt: string | null;
    sentBy: string | null;
};

export type ContractRecipient = {
    id: string;
    season: string;
    client: { displayName: string; email: string | null };
    mail: ContractMail;
};
