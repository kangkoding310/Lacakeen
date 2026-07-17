export interface Integration {
    id: string;
    type: string;
    name: string;
    status: 'connected' | 'not_connected';
    google_account_email?: string | null;
}
