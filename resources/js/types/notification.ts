export interface NotificationData {
    message: string;
    url?: string;
}

export interface NotificationItem {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
}
