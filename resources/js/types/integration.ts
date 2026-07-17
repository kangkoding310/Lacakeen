export interface Integration {
    id: string;
    type: string;
    name: string;
    status: 'connected' | 'disconnected';
}
