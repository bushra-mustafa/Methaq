export type PaymentStatus = 'pending' | 'completed' | 'failed';

export interface PaymentDTO {
    orderId: string;
    status: PaymentStatus;
    amountMinor: string;
    currency: string;
}
