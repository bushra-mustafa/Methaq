import type { PaymentState } from '../../../Types/PaymentDTO';

export const paymentStateLabels: Record<PaymentState, string> = {
    awaiting_receipt: 'بانتظار الدفع',
    pending_review: 'القسيمة قيد المراجعة',
    rejected: 'رُفضت القسيمة',
    paid: 'تم اعتماد الدفع',
};
