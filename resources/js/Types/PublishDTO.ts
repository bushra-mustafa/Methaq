export type PublishState = 'published' | 'ready' | 'blocked';

export interface PublishStateDTO {
    state: PublishState;
    canPublish: boolean;
    blocker: string | null;
    willRemove: string[];
    invitationUrl: string;
}

/** The studio's refresh panel: whether a live invitation can take the saved draft, and what it would drop. */
export interface PublishedRefreshStateDTO {
    canRefresh: boolean;
    blocker: string | null;
    willRemove: string[];
    publishedRevision: number | null;
    invitationUrl: string;
}

/** The customer an administrator is editing for in the studio. */
export interface StudioOwnerDTO {
    name: string;
    email: string;
}

/** The note on the owner's event page after the Methaq team changed her design. */
export interface TeamDesignEditDTO {
    editedAt: string;
    refreshedAt: string | null;
    inPublishedVersion: boolean;
}
