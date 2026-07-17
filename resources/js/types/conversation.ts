export interface ChatParticipant {
    id: number;
    name: string;
    avatar: string | null;
}

export interface ChatMessage {
    id: string;
    body: string;
    sender_id: number;
    sender: ChatParticipant;
    created_at: string;
}

export interface ConversationSummary {
    id: string;
    type: 'direct' | 'group';
    name: string | null;
    participants: ChatParticipant[];
    messages?: ChatMessage[];
}
