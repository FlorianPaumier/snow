export type User = {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Organization = {
    id: string;
    name: string;
    ownerId: string | null;
};

export type Subscription = {
    plan: string;
    status: string;
    onTrial: boolean;
    trialEndsAt: string | null;
};

export type Auth = {
    user: User;
    organization?: Organization | null;
    subscription?: Subscription | null;
    can?: {
        manageUsers?: boolean;
        manageClients?: boolean;
        manageContracts?: boolean;
        manageBilling?: boolean;
        manageOrganization?: boolean;
    };
};
