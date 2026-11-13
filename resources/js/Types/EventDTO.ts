import type { EventCategory } from './Library';

export type EventStatus = 'draft' | 'published' | 'expired';
export type EventPresentationState = 'draft' | 'preparing' | 'ready' | 'expired';

export interface EventTemplateDTO {
    id: string;
    slug: string;
    name: string;
    thumbnailUrl: string;
    thumbnailWebpUrl: string | null;
    thumbnailAvifUrl: string | null;
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
    invitationUrl: string;
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

export interface RsvpSummary {
    responses: number;
    attending: number;
    companions: number;
    headcount: number;
    declined: number;
    maybe: number;
}

export type RsvpStatus = 'attending' | 'declined' | 'maybe';

export interface RsvpRow {
    id: string;
    guestName: string;
    guestPhone: string | null;
    status: RsvpStatus;
    companions: number;
    notes: string | null;
    submittedAt: string | null;
}
