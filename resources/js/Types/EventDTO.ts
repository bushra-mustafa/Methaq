export type EventStatus = 'draft' | 'published' | 'expired';
export type EventPresentationState = 'draft' | 'preparing' | 'ready' | 'expired';

export interface EventDTO {
    id: string;
    title: string;
    subdomain: string;
    eventDate: string;
    timezone: string;
    expiresAt: string;
    status: EventStatus;
    isPaid: boolean;
    presentationState: EventPresentationState;
}
