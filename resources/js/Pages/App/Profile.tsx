import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AppShell } from '../../Domains/Users/Components/AppShell';
import { FormField } from '../../Domains/Users/Components/FormField';
import { TwoFactorPanel } from '../../Domains/Users/Components/TwoFactorPanel';

interface ProfileUser { name: string; email: string; phone: string | null; emailVerified: boolean }
interface UserSession { fingerprint: string; isCurrentDevice: boolean; ipAddress: string | null; userAgent: string; lastActiveAt: string }
interface ProfileProps {
    user: ProfileUser;
    sessions: UserSession[];
    twoFactor: { enabled: boolean; pendingConfirmation: boolean };
    status?: string | null;
}

export default function Profile({ user, sessions, twoFactor, status }: ProfileProps) {
    const profile = useForm({ name: user.name, email: user.email, phone: user.phone ?? '' });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });
    const sessionsForm = useForm({ password: '' });

    function updateProfile(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        profile.put('/user/profile-information', { preserveScroll: true });
    }
    function updatePassword(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        password.put('/user/password', { preserveScroll: true, onSuccess: () => password.reset() });
    }
    function logoutOthers(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        sessionsForm.delete('/app/sessions/others', { preserveScroll: true, onSuccess: () => sessionsForm.reset() });
    }

    return <AppShell eyebrow="الحساب والأمان" title="تفاصيلك تحت سيطرتك">
        <Head title="الحساب والأمان" />
        {status && <p className="account-status" role="status">تم حفظ التغيير بنجاح.</p>}
        <div className="profile-grid">
            <section className="account-section" aria-labelledby="profile-title">
                <div className="account-section-heading"><div><span>بيانات الحساب</span><h2 id="profile-title">معلوماتك</h2></div><strong className={user.emailVerified ? 'is-positive' : ''}>{user.emailVerified ? 'البريد مؤكد' : 'غير مؤكد'}</strong></div>
                <form className="account-form" onSubmit={updateProfile} noValidate>
                    <FormField label="الاسم" name="name" value={profile.data.name} onChange={(event) => profile.setData('name', event.target.value)} autoComplete="name" error={profile.errors.name} required />
                    <FormField label="البريد الإلكتروني" name="email" type="email" value={profile.data.email} onChange={(event) => profile.setData('email', event.target.value)} autoComplete="email" error={profile.errors.email} required />
                    <FormField label="رقم الهاتف · اختياري" name="phone" type="tel" value={profile.data.phone} onChange={(event) => profile.setData('phone', event.target.value)} autoComplete="tel" error={profile.errors.phone} />
                    <button className="account-primary" type="submit" disabled={profile.processing || !profile.isDirty}>حفظ البيانات</button>
                </form>
            </section>
            <section className="account-section" aria-labelledby="password-title">
                <div className="account-section-heading"><div><span>كلمة المرور</span><h2 id="password-title">تغيير كلمة المرور</h2></div></div>
                <form className="account-form" onSubmit={updatePassword} noValidate>
                    <FormField label="كلمة المرور الحالية" name="current_password" type="password" value={password.data.current_password} onChange={(event) => password.setData('current_password', event.target.value)} autoComplete="current-password" error={password.errors.current_password} required />
                    <FormField label="كلمة المرور الجديدة" name="password" type="password" value={password.data.password} onChange={(event) => password.setData('password', event.target.value)} autoComplete="new-password" error={password.errors.password} required />
                    <FormField label="تأكيد كلمة المرور" name="password_confirmation" type="password" value={password.data.password_confirmation} onChange={(event) => password.setData('password_confirmation', event.target.value)} autoComplete="new-password" error={password.errors.password_confirmation} required />
                    <button className="account-primary" type="submit" disabled={password.processing}>تغيير كلمة المرور</button>
                </form>
            </section>
            <TwoFactorPanel {...twoFactor} />
            <section className="account-section account-sessions" aria-labelledby="sessions-title">
                <div className="account-section-heading"><div><span>الأجهزة</span><h2 id="sessions-title">الجلسات المفتوحة</h2></div></div>
                {sessions.length === 0 ? <p>لا توجد جلسات أخرى ظاهرة الآن.</p> : <ul>{sessions.map((session) => <li key={session.fingerprint}><div><strong>{session.isCurrentDevice ? 'هذا الجهاز' : 'جلسة أخرى'}</strong><span dir="ltr">{session.ipAddress ?? 'IP غير متاح'}</span></div><p>{session.userAgent}</p><time dateTime={session.lastActiveAt}>{new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(session.lastActiveAt))}</time></li>)}</ul>}
                <form className="account-inline-form" onSubmit={logoutOthers}>
                    <FormField label="كلمة المرور لتسجيل خروج الأجهزة الأخرى" name="session_password" type="password" value={sessionsForm.data.password} onChange={(event) => sessionsForm.setData('password', event.target.value)} autoComplete="current-password" error={sessionsForm.errors.password} required />
                    <button className="account-secondary" type="submit" disabled={sessionsForm.processing}>تسجيل خروج الأجهزة الأخرى</button>
                </form>
            </section>
        </div>
    </AppShell>;
}
