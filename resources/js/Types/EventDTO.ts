import type { EventCategory } from './Library';

export type EventStatus = 'draft' | 'published' | 'expired';
export type EventPresentationState = 'draft' | 'preparing' | 'ready' | 'expired';

export interface EventTemplateDTO {
    id: string;
    slug: string;
    name: string;
    thumbnailUrl: string;
}

export interface EventDTO {
    id: string;
    title: string;
    category: EventCategory;
    categoryLabel: string;
    subdomain: string;
    eventDate: string;
    timezone: string;
    expiresAt: string;
    status: EventStatus;
    isPaid: boolean;
    presentationState: EventPresentationState;
    template: EventTemplateDTO | null;
    designRevision: number;
}

export interface EventTemplateOption {
    id: string;
    slug: string;
    name: string;
    category: EventCategory;
    thumbnailUrl: string;
}

export interface SelectOption<TValue extends string> {
    value: TValue;
    label: string;
}
