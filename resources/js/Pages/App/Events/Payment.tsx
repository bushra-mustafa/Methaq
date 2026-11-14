import { EventJourney } from '../../../Domains/Events/Components/EventJourney';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { ChangeEvent, FormEvent } from 'react';
import { paymentStateLabels } from '../../../Domains/Payments/Services/paymentState';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { EventDTO } from '../../../Types/EventDTO';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { EventPaymentDTO } from '../../../Types/PaymentDTO';
import '../../../../css/events.css';

interface PaymentPageProps {
    event: EventDTO;
    payment: EventPaymentDTO;
}

interface ReceiptForm {
    receipt: File | null;
}

function formatDate(value: string, timezone: string): string {
    return new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }).format(new Date(value));
}

export default function EventPayment({ event, payment }: PaymentPageProps) {
    const { flash } = usePage<SharedPageProps>().props;
    const form = useForm<ReceiptForm>({ receipt: null });

    function chooseFile(change: ChangeEvent<HTMLInputElement>): void {
        form.setData('receipt', change.target.files?.[0] ?? null);
        form.clearErrors('receipt');
    }

    function submit(submitEvent: FormEvent<HTMLFormElement>): void {
        submitEvent.preventDefault();
        form.post(`/app/events/${event.id}/payment`, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
    }

    const account = payment.bankAccount;

    return <AppShell eyebrow={event.title} title="الدفع بالتحويل البنكي">
        <Head title={`الدفع · ${event.title}`} />
        <EventJourney event={event} current="payment" />
        {flash.status === 'receipt-submitted' && <aside className="event-success" role="status"><strong>وصلتنا القسيمة</strong><span>سنراجعها بعد التأكد من وصول المبلغ، ويظهر القرار في هذه الصفحة.</span></aside>}

        <section className="payment-layout">
            <div className="payment-summary">
                <span className={`event-state payment-state is-${payment.state}`}>{paymentStateLabels[payment.state]}</span>
                <dl>
                    <div><dt>المناسبة</dt><dd>{event.title}</dd></div>
                    <div><dt>القالب</dt><dd>{event.template?.name ?? 'بطاقة فارغة'}</dd></div>
                    <div><dt>المبلغ</dt><dd className="payment-amount">{payment.priceLabel ?? 'لم يُحدَّد بعد'}</dd></div>
                </dl>
                {payment.includedFeatures.length > 0 && <div className="payment-features"><strong>يشمل الدفع</strong><ul>{payment.includedFeatures.map((feature) => <li key={feature}>{feature}</li>)}</ul></div>}
                {payment.lockedFeatures.length > 0 && <div className="payment-features is-locked"><strong>غير متاح في هذا القالب</strong><ul>{payment.lockedFeatures.map((feature) => <li key={feature}>{feature}</li>)}</ul></div>}
                {payment.latestOrder && <div className="payment-latest"><strong>آخر قسيمة</strong><p>{payment.latestOrder.amountLabel} · أُرسلت {formatDate(payment.latestOrder.submittedAt, event.timezone)}{payment.latestOrder.reviewedAt && <> · رُوجعت {formatDate(payment.latestOrder.reviewedAt, event.timezone)}</>}</p></div>}
            </div>

            <div className="payment-action">
                {payment.state === 'paid' && <div className="event-next-step"><strong>تم اعتماد الدفع</strong><p>جهّزي دعوتك وانشريها من صفحة الظرف والفتح.</p><Link className="account-primary" href={`/app/events/${event.id}/presentation`}>الانتقال إلى النشر</Link></div>}
                {payment.state === 'pending_review' && <div className="event-next-step"><strong>القسيمة قيد المراجعة</strong><p>نتحقق من وصول المبلغ إلى الحساب، ثم يظهر القرار هنا. لا حاجة لإرسالها مرة أخرى.</p></div>}
                {payment.state === 'rejected' && payment.canSubmit && <p className="account-error" role="alert">{payment.rejectionMessage}</p>}
                {payment.blocker && payment.state !== 'paid' && payment.state !== 'pending_review' && <p className="account-error" role="alert">{payment.blocker}</p>}

                {payment.canSubmit && account && <>
                    <ol className="payment-steps">
                        <li><strong>حوّلي المبلغ {payment.priceLabel}</strong><span>إلى الحساب التالي:</span>
                            <dl className="payment-account">
                                {account.bankName && <div><dt>المصرف</dt><dd>{account.bankName}</dd></div>}
                                {account.accountName && <div><dt>اسم الحساب</dt><dd>{account.accountName}</dd></div>}
                                <div><dt>رقم الحساب</dt><dd dir="ltr">{account.accountNumber}</dd></div>
                                {account.iban && <div><dt>IBAN</dt><dd dir="ltr">{account.iban}</dd></div>}
                            </dl>
                        </li>
                        <li><strong>ارفعي صورة القسيمة</strong><span>صورة واضحة (JPG أو PNG أو WEBP) أو PDF، حتى 5 ميغابايت. تُحفظ بشكل خاص ولا يراها إلا فريق المراجعة.</span></li>
                    </ol>
                    <form className="payment-form" onSubmit={submit} noValidate>
                        <label className="account-field" htmlFor="payment-receipt"><span>صورة القسيمة</span><input id="payment-receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" onChange={chooseFile} required />{form.errors.receipt && <small role="alert">{form.errors.receipt}</small>}</label>
                        {form.progress && <progress value={form.progress.percentage} max={100}>{form.progress.percentage}%</progress>}
                        <button className="account-primary" type="submit" disabled={form.processing || form.data.receipt === null} aria-busy={form.processing}>{form.processing ? 'جاري رفع القسيمة…' : 'إرسال القسيمة للمراجعة'}</button>
                    </form>
                </>}

                <div className="event-overview-actions"><Link className="account-secondary" href={`/app/events/${event.id}`}>الرجوع إلى المناسبة</Link></div>
            </div>
        </section>
    </AppShell>;
}
