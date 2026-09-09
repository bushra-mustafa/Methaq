export interface AuthenticatedUser {
    id: string;
    name: string;
    email: string;
    role: 'customer' | 'admin';
    emailVerified: boolean;
}

export interface SharedPageProps {
    auth: { user: AuthenticatedUser | null };
    flash: { status?: string | null };
    errors: Record<string, string>;
    [key: string]: unknown;
}
