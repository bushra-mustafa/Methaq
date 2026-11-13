export type PublishState = 'published' | 'ready' | 'blocked';

export interface PublishStateDTO {
    state: PublishState;
    canPublish: boolean;
    blocker: string | null;
    willRemove: string[];
    invitationUrl: string;
}
