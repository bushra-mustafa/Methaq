import { InvitationProgressPanel } from '../../../Domains/Events/Components/InvitationProgressPanel';
import type { InvitationProgress } from '../../../Types/InvitationProgress';
import { InvitationPreview } from '../../../Domains/Editor/Components/InvitationPreview';
import { EventJourney } from '../../../Domains/Events/Components/EventJourney';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type ChangeEvent, type FormEvent, type ReactNode } from 'react';
import { WhatsAppVerification, type WhatsAppState } from '../../../Domains/Users/Components/WhatsAppVerification';
import { usePresentationCard } from '../../../Domains/Editor/Hooks/usePresentationCard';
import { parseDesignDocument } from '../../../Domains/Editor/Services/DesignDocumentSerializer';
import { pngFileFromDataUrl } from '../../../Domains/Editor/Services/pngFileFromDataUrl';
import type { DesignDocument } from '../../../Types/DesignDocument';
import type { EditorAsset } from '../../../Types/Editor';
import '../../../../css/presentation.css';
import { FreeOfferCard } from '../../../Domains/Payments/Components/FreeOfferCard';
import { paymentStateLabels } from '../../../Domains/Payments/Services/paymentState';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { EventDTO } from '../../../Types/EventDTO';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { EventPaymentDTO } from '../../../Types/PaymentDTO';
import '../../../../css/events.css';

interface PaymentPageProps {
    event: EventDTO;
    payment: EventPaymentDTO;
    whatsapp: WhatsAppState;
    progress: InvitationProgress;
    preparation: { document: unknown; revision: number; assets: EditorAsset[] } | null;
}

interface ReceiptForm {
    receipt: File | null;
}

function formatDate(value: string, timezone: string): string {
    return new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }).format(new Date(value));
}

function PaymentForm({ event, payment, whatsapp, progress, preview = null, reviewConfirmed = false, cardUrl = null, revision = null, preparationError = null }: PaymentPageProps & { cardUrl?: string | null; revision?: number | null; preparationError?: string | null; preview?: ReactNode; reviewConfirmed?: boolean }) {
    const [preparing, setPreparing] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const { flash, errors } = usePage<SharedPageProps>().props;
    const form = useForm<ReceiptForm>({ receipt: null });

    function chooseFile(change: ChangeEvent<HTMLInputElement>): void {
        form.setData('receipt', change.target.files?.[0] ?? null);
        form.clearErrors('receipt');
    }

    function submit(submitEvent: FormEvent<HTMLFormElement>): void {
        submitEvent.preventDefault();
        setLocalError(null);
        if (!reviewConfirmed) { setLocalError('افتحي المعاينة وأكدي الأسماء والموعد قبل إرسال القسيمة.'); return; }
        if (whatsapp.enabled && (!whatsapp.verified || !cardUrl || revision === null)) return;
        try {
            form.transform((data) => ({ ...data, ...(whatsapp.enabled && cardUrl ? { render: pngFileFromDataUrl(cardUrl), expectedRevision: revision } : {}) }));
            form.post(`/app/events/${event.id}/payment`, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
        } catch {
            setLocalError('تعذّر تجهيز صورة الدعوة. حدّثي الصفحة وحاولي مرة أخرى.');
        }
    }

    function prepare(): void {
        if (!cardUrl || revision === null) return;
        setLocalError(null);
        try {
            setPreparing(true);
            router.post(`/app/events/${event.id}/prepare`, { render: pngFileFromDataUrl(cardUrl), expectedRevision: revision }, { forceFormData: true, preserveScroll: true, onFinish: () => setPreparing(false) });
        } catch {
            setPreparing(false);
            setLocalError('تعذّر تجهيز الدعوة. حاولي مرة أخرى.');
        }
    }
    const accounts = payment.bankAccounts;
    const ready = reviewConfirmed && (!whatsapp.enabled || (whatsapp.verified && cardUrl !== null && !preparationError));
    const error = localError ?? preparationError ?? errors.render ?? errors.expectedRevision ?? errors.phone;

    return <AppShell eyebrow={event.title} title="الدفع بالتحويل البنكي">
        <Head title={`الدفع · ${event.title}`} />
        <EventJourney event={event} current="payment" />
        <InvitationProgressPanel progress={progress} timezone={event.timezone} />
        {preview}
        {payment.canSubmit && !preview && <p className="account-error" role="alert">افتحي محرر البطاقة واحفظي التصميم لتظهر المعاينة قبل الدفع. <Link href={`/app/events/${event.id}/editor`}>الذهاب للمحرر</Link></p>}
        {flash.status === 'free-offer-claimed' && <aside className="event-success" role="status"><strong>فُعّلت مناسبتك مجاناً</strong><span>استفدتِ من العرض. جهّزي دعوتك وانشريها من صفحة الظرف والفتح.</span></aside>}
        {flash.status === 'receipt-submitted' && <aside className="event-success" role="status"><strong>وصلتنا القسيمة</strong><span>سنراجعها بعد التأكد من وصول المبلغ، ويظهر القرار في هذه الصفحة.</span></aside>}

        {flash.status === 'whatsapp-code-queued' && <p className="event-success" role="status">طلبنا رمز التأكيد على واتساب. انتظري وصوله ثم اكتبيه هنا.</p>}
        {flash.status === 'whatsapp-verified' && <p className="event-success" role="status">تم تأكيد رقم واتساب.</p>}
        {flash.status === 'invitation-prepared' && <p className="event-success" role="status">الدعوة جاهزة. بعد اعتماد الدفع، تقدري تنشريها بالرد بكلمة «نشر» على واتساب.</p>}
        {error && <p className="account-error" role="alert">{error}</p>}
        {whatsapp.enabled && <WhatsAppVerification state={whatsapp} />}
        {whatsapp.enabled && event.status === 'draft' && <section className="payment-whatsapp" aria-label="نسخة الدعوة الجاهزة">
            <p>{whatsapp.prepared ? 'نسختك الحالية محفوظة وجاهزة للنشر من واتساب بعد اعتماد الدفع.' : payment.canSubmit ? 'عند رفع القسيمة، نحفظ نسخة الدعوة الحالية للنشر لاحقاً من واتساب.' : 'جهّزي نسخة الدعوة الحالية للنشر من واتساب؛ لا يلزم دفع جديد.'}</p>
            {!cardUrl && !preparationError && <p role="status">نجهّز صورة الدعوة…</p>}
            {!payment.canSubmit && !whatsapp.prepared && <button className="account-secondary" type="button" disabled={!cardUrl || preparing} onClick={prepare}>{preparing ? 'جاري حفظ النسخة…' : 'تجهيز النسخة الحالية للنشر من واتساب'}</button>}
            <p>أي تعديل على التصميم أو العرض يلغي جاهزية النسخة القديمة حتى تجهزي الجديدة هنا.</p>
        </section>}
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
                {payment.latestOrder && !payment.paidByOffer && <div className="payment-latest"><strong>آخر قسيمة</strong><p>{payment.latestOrder.amountLabel} · أُرسلت {formatDate(payment.latestOrder.submittedAt, event.timezone)}{payment.latestOrder.reviewedAt && <> · رُوجعت {formatDate(payment.latestOrder.reviewedAt, event.timezone)}</>}</p></div>}
            </div>

            <div className="payment-action">
                {payment.freeOffer && payment.state !== 'paid' && <FreeOfferCard eventId={event.id} offer={payment.freeOffer} />}
                {payment.state === 'paid' && <div className="event-next-step"><strong>{payment.paidByOffer ? 'فُعّلت مناسبتك مجاناً ضمن العرض' : 'تم اعتماد الدفع'}</strong><p>{whatsapp.enabled ? 'بعد تجهيز النسخة الحالية، تقدري تنشري من واتساب بالرد بكلمة «نشر»، أو من الموقع.' : 'جهّزي دعوتك وانشريها من صفحة الظرف والفتح.'}</p><Link className="account-primary" href={`/app/events/${event.id}/presentation`}>الانتقال إلى النشر</Link></div>}
                {payment.state === 'pending_review' && <div className="event-next-step"><strong>القسيمة قيد المراجعة</strong><p>نتحقق من وصول المبلغ إلى الحساب، ثم يظهر القرار هنا. لا حاجة لإرسالها مرة أخرى.</p></div>}
                {payment.state === 'rejected' && payment.canSubmit && <p className="account-error" role="alert">{payment.rejectionMessage}</p>}
                {payment.blocker && payment.state !== 'paid' && payment.state !== 'pending_review' && <p className="account-error" role="alert">{payment.blocker}</p>}

                {payment.canSubmit && accounts.length > 0 && <>
                    <ol className="payment-steps">
                        <li><strong>حوّلي المبلغ {payment.priceLabel}</strong><span>{accounts.length > 1 ? 'إلى أحد الحسابات التالية:' : 'إلى الحساب التالي:'}</span>
                            {accounts.map((account, index) => <dl className="payment-account" key={`${account.bankName}-${account.accountNumber ?? account.iban}-${index}`}>
                                {account.bankName && <div><dt>المصرف</dt><dd>{account.bankName}</dd></div>}
                                {account.accountName && <div><dt>اسم الحساب</dt><dd>{account.accountName}</dd></div>}
                                {account.accountNumber && <div><dt>رقم الحساب</dt><dd dir="ltr">{account.accountNumber}</dd></div>}
                                {account.iban && <div><dt>IBAN</dt><dd dir="ltr">{account.iban}</dd></div>}
                            </dl>)}
                        </li>
                        <li><strong>ارفعي صورة القسيمة</strong><span>صورة واضحة (JPG أو PNG أو WEBP) أو PDF، حتى 5 ميغابايت. تُحفظ بشكل خاص ولا يراها إلا فريق المراجعة.</span></li>
                    </ol>
                    <form className="payment-form" onSubmit={submit} noValidate>
                        <label className="account-field" htmlFor="payment-receipt"><span>صورة القسيمة</span><input id="payment-receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" onChange={chooseFile} required />{form.errors.receipt && <small role="alert">{form.errors.receipt}</small>}</label>
                        {form.progress && <progress value={form.progress.percentage} max={100}>{form.progress.percentage}%</progress>}
                        <button className="account-primary" type="submit" disabled={form.processing || form.data.receipt === null || !ready} aria-busy={form.processing}>{form.processing ? 'جاري رفع القسيمة…' : 'إرسال القسيمة للمراجعة'}</button>
                    </form>
                </>}

                <div className="event-overview-actions"><Link className="account-secondary" href={`/app/events/${event.id}`}>الرجوع إلى المناسبة</Link></div>
            </div>
        </section>
    </AppShell>;
}

function PreparedPayment(props: PaymentPageProps & { parsedDocument: DesignDocument; preparation: NonNullable<PaymentPageProps['preparation']> }) {
    const card = usePresentationCard(props.parsedDocument, props.preparation.assets);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [openedRevision, setOpenedRevision] = useState<number | null>(null);
    const [reviewedRevision, setReviewedRevision] = useState<number | null>(null);
    const revision = props.preparation.revision;
    const preview = <section className="payment-final-preview" aria-labelledby="payment-preview-title"><div><p>قبل التحويل ورفع القسيمة</p><h2 id="payment-preview-title">راجعي دعوتك كما سيشوفها ضيوفك</h2><p>تأكدي من الأسماء والموعد وشكل الدعوة. المعاينة تعرض الميزات المشمولة فقط، ولا تنشر الرابط.</p></div><button type="button" className="account-secondary" disabled={!card.cardUrl || Boolean(card.error)} onClick={() => { setOpenedRevision(revision); setPreviewOpen(true); }}>فتح المعاينة النهائية</button>{!card.cardUrl && !card.error && <p role="status">نجهّز المعاينة…</p>}{props.payment.canSubmit && <label className="payment-preview-confirm"><input type="checkbox" disabled={openedRevision !== revision} checked={reviewedRevision === revision} onChange={(event) => setReviewedRevision(event.target.checked ? revision : null)} /><span>راجعت الأسماء والموعد ومعاينة الدعوة الحالية.</span></label>}</section>;
    return <><PaymentForm {...props} preview={preview} reviewConfirmed={reviewedRevision === revision} cardUrl={card.cardUrl} revision={revision} preparationError={card.error} /><div className="presentation-render-source" ref={card.host} aria-hidden="true" inert />{previewOpen && card.cardUrl && <InvitationPreview assets={props.preparation.assets} cardParts={card.parts} scene={props.parsedDocument.scene} palette={props.parsedDocument.palette} cardUrl={card.cardUrl} title={props.event.title} onClose={() => setPreviewOpen(false)} />}</>;
}

export default function EventPayment(props: PaymentPageProps) {
    if (!props.preparation || props.event.status !== 'draft') return <PaymentForm {...props} />;
    try {
        return <PreparedPayment {...props} preparation={props.preparation} parsedDocument={parseDesignDocument(props.preparation.document)} />;
    } catch {
        return <PaymentForm {...props} preparationError="تعذّر تحميل التصميم. ارجعي للمحرر واحفظي الدعوة قبل رفع القسيمة." />;
    }
}
