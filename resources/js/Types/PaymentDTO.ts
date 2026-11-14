import type { FreeOfferDTO } from './FreeOffer';

export type PaymentState = 'paid' | 'pending_review' | 'rejected' | 'awaiting_receipt';
export type OrderStatus = 'pending' | 'completed' | 'failed';

export interface BankAccountDTO {
    bankName: string | null;
    accountName: string | null;
    accountNumber: string | null;
    iban: string | null;
}

export interface LatestOrderDTO {
    amountLabel: string;
    status: OrderStatus;
    submittedAt: string;
    reviewedAt: string | null;
}

export interface EventPaymentDTO {
    state: PaymentState;
    priceLabel: string | null;
    bankAccounts: BankAccountDTO[];
    includedFeatures: string[];
    lockedFeatures: string[];
    canSubmit: boolean;
    blocker: string | null;
    latestOrder: LatestOrderDTO | null;
    rejectionMessage: string | null;
    freeOffer: FreeOfferDTO | null;
    paidByOffer: boolean;
}
