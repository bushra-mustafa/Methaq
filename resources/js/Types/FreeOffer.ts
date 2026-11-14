/** A live free offer as the server summarises it. */
export interface FreeOfferDTO {
    title: string;
    description: string | null;
    spotsTotal: number;
    spotsLeft: number;
    endsAt: string;
}

export interface HomeFreeOfferDTO extends FreeOfferDTO {
    templateName: string | null;
    ctaHref: string;
}
