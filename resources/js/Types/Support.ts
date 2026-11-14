export interface ContactChannelDTO {
    id: number;
    name: string;
    url: string;
    icon: ContactIconKey;
}

export type ContactIconKey = 'envelope' | 'phone' | 'whatsapp' | 'instagram' | 'facebook' | 'telegram' | 'x' | 'tiktok' | 'snapchat' | 'youtube' | 'globe';

export type SupportTopicKey = 'problem' | 'custom_design';

export interface SupportThreadRow {
    id: number;
    topic: SupportTopicKey;
    topicLabel: string;
    subject: string;
    status: 'open' | 'closed';
    unread: boolean;
    lastMessageAt: string;
}

export interface SupportMessageRow {
    id: number;
    fromTeam: boolean;
    body: string;
    createdAt: string;
}
