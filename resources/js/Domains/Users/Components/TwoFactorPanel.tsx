import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { FormField } from './FormField';

interface TwoFactorPanelProps {
    enabled: boolean;
    pendingConfirmation: boolean;
}

interface QrCodeResponse { svg: string }

export function TwoFactorPanel({ enabled, pendingConfirmation }: TwoFactorPanelProps) {
    const [qrSvg, setQrSvg] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [loadingSecrets, setLoadingSecrets] = useState(false);
    const [secretError, setSecretError] = useState('');
    const confirmation = useForm({ code: '' });

    async function loadSetupDetails(): Promise<void> {
        setLoadingSecrets(true);
        setSecretError('');
        try {
            const [qrResponse, codesResponse] = await Promise.all([
                axios.get<QrCodeResponse>('/user/two-factor-qr-code'),
                axios.get<string[]>('/user/two-factor-recovery-codes'),
            ]);
            setQrSvg(qrResponse.data.svg);
            setRecoveryCodes(codesResponse.data);
        } catch {
            setSecretError('أكدي كلمة المرور ثم أعيدي فتح إعدادات التحقق بخطوتين.');
        } finally {
            setLoadingSecrets(false);
        }
    }

    useEffect(() => {
        if (pendingConfirmation) void loadSetupDetails();
        if (!enabled && !pendingConfirmation) {
            setQrSvg(null);
            setRecoveryCodes([]);
        }
    }, [enabled, pendingConfirmation]);

    function enable(): void {
        router.post('/user/two-factor-authentication', {}, { preserveScroll: true });
    }

    function confirm(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        confirmation.post('/user/confirmed-two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => confirmation.reset('code'),
        });
    }

    function disable(): void {
        router.delete('/user/two-factor-authentication', { preserveScroll: true });
    }

    function regenerateCodes(): void {
        router.post('/user/two-factor-recovery-codes', {}, {
            preserveScroll: true,
            onSuccess: () => void loadSetupDetails(),
        });
    }

    return <section className="account-section" aria-labelledby="two-factor-title">
        <div className="account-section-heading"><div><span>حماية إضافية</span><h2 id="two-factor-title">التحقق بخطوتين</h2></div><strong className={enabled ? 'is-positive' : ''}>{enabled ? 'مفعّل' : pendingConfirmation ? 'بانتظار التأكيد' : 'غير مفعّل'}</strong></div>
        {!enabled && !pendingConfirmation && <><p>احمي الحساب برمز مؤقت من تطبيق المصادقة. سيكون إلزامياً قبل استخدام صفحات الإدارة.</p><button className="account-secondary" type="button" onClick={enable}>بدء الإعداد</button></>}
        {pendingConfirmation && <div className="two-factor-setup">
            <p>امسحي الرمز بتطبيق المصادقة، ثم أدخلي الرمز الظاهر لإكمال التفعيل.</p>
            {loadingSecrets && <p role="status">جاري تجهيز رمز المصادقة…</p>}
            {secretError && <p className="account-error" role="alert">{secretError}</p>}
            {qrSvg && <img className="two-factor-qr" src={`data:image/svg+xml,${encodeURIComponent(qrSvg)}`} alt="رمز QR لإضافة حساب ميثاق إلى تطبيق المصادقة" />}
            <form className="account-inline-form" onSubmit={confirm}>
                <FormField label="رمز التحقق" name="code" inputMode="numeric" autoComplete="one-time-code" value={confirmation.data.code} onChange={(event) => confirmation.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))} error={confirmation.errors.code} required />
                <button className="account-primary" type="submit" disabled={confirmation.processing}>تأكيد التفعيل</button>
            </form>
        </div>}
        {enabled && <div className="two-factor-enabled">
            <p>احتفظي برموز الاسترداد في مكان آمن. كل رمز يستخدم مرة واحدة.</p>
            {recoveryCodes.length === 0
                ? <button className="account-secondary" type="button" onClick={() => void loadSetupDetails()}>عرض رموز الاسترداد</button>
                : <><ul className="recovery-codes" aria-label="رموز الاسترداد">{recoveryCodes.map((code) => <li key={code}><code>{code}</code></li>)}</ul><button className="account-secondary" type="button" onClick={regenerateCodes}>إنشاء رموز جديدة</button></>}
            <button className="account-danger" type="button" onClick={disable}>إيقاف التحقق بخطوتين</button>
        </div>}
    </section>;
}
