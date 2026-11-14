import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { SupportMessageRow } from '../../../Types/Support';

interface SupportShowProps {
    thread: { id: number; topicLabel: string; subject: string; closed: boolean };
    messages: SupportMessageRow[];
}

function formatTime(value: string): string {
    return new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

export default function SupportShow({ thread, messages }: SupportShowProps) {
    const { flash } = usePage<SharedPageProps>().props;
    const form = useForm({ body: '' });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post(`/app/support/${thread.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return <AppShell title={thread.subject}>
        <Head title={thread.subject} />
        {flash.status === 'support-sent' && <p className="account-status" role="status">وصلت رسالتك إلى الفريق، وسنرد عليك هنا.</p>}
        <p><Link className="account-secondary" href="/app/support">كل الرسائل</Link> <span>{thread.topicLabel}</span></p>
        <ul className="support-messages" aria-label="المحادثة">
            {messages.map((message) => <li key={message.id} className={message.fromTeam ? 'is-team' : 'is-mine'}>
                <div><strong>{message.fromTeam ? 'فريق ميثاق' : 'أنتِ'}</strong><time dateTime={message.createdAt}>{formatTime(message.createdAt)}</time></div>
                <p>{message.body}</p>
            </li>)}
        </ul>
        {thread.closed ? <p className="account-status" role="note">أُغلقت هذه المحادثة. افتحي رسالة جديدة إن احتجتِ شيئاً آخر.</p> : <form className="account-form" onSubmit={submit} noValidate>
            <label className="account-field" htmlFor="support-reply">
                <span>ردّك</span>
                <textarea id="support-reply" name="body" rows={4} maxLength={3000} value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} aria-invalid={Boolean(form.errors.body)} aria-describedby={form.errors.body ? 'support-reply-error' : undefined} required />
                {form.errors.body && <small id="support-reply-error" role="alert">{form.errors.body}</small>}
            </label>
            <button className="account-primary" type="submit" disabled={form.processing} aria-busy={form.processing}>إرسال الرد</button>
        </form>}
    </AppShell>;
}
