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
    analytics?: { enabled: boolean; domain: string | null; script: string | null; exclude: string | null };
    [key: string]: unknown;
}
