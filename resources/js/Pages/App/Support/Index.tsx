import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import { FormField } from '../../../Domains/Users/Components/FormField';
import type { SupportThreadRow, SupportTopicKey } from '../../../Types/Support';

interface SupportIndexProps {
    threads: SupportThreadRow[];
    topics: { value: SupportTopicKey; label: string }[];
}

function formatTime(value: string): string {
    return new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

export default function SupportIndex({ threads, topics }: SupportIndexProps) {
    const form = useForm<{ topic: SupportTopicKey; subject: string; body: string }>({ topic: topics[0]?.value ?? 'problem', subject: '', body: '' });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/app/support', { preserveScroll: true, onSuccess: () => form.reset('subject', 'body') });
    }

    return <AppShell title="رسائلي">
        <Head title="رسائلي" />
        <div className="support-grid">
            <section className="account-section" aria-labelledby="support-new">
                <div className="account-section-heading"><div><span>رسالة جديدة</span><h2 id="support-new">راسلي الفريق</h2></div></div>
                <form className="account-form" onSubmit={submit} noValidate>
                    <label className="account-field" htmlFor="support-topic">
                        <span>نوع الرسالة</span>
                        <select id="support-topic" name="topic" value={form.data.topic} onChange={(event) => form.setData('topic', event.target.value as SupportTopicKey)} aria-invalid={Boolean(form.errors.topic)}>
                            {topics.map((topic) => <option key={topic.value} value={topic.value}>{topic.label}</option>)}
                        </select>
                        {form.errors.topic && <small role="alert">{form.errors.topic}</small>}
                    </label>
                    <FormField label="العنوان" name="support_subject" value={form.data.subject} maxLength={120} onChange={(event) => form.setData('subject', event.target.value)} error={form.errors.subject} required />
                    <label className="account-field" htmlFor="support-body">
                        <span>{form.data.topic === 'custom_design' ? 'صفي التصميم الذي تريدينه' : 'اشرحي المشكلة'}</span>
                        <textarea id="support-body" name="body" rows={6} maxLength={3000} value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} aria-invalid={Boolean(form.errors.body)} aria-describedby={form.errors.body ? 'support-body-error' : undefined} required />
                        {form.errors.body && <small id="support-body-error" role="alert">{form.errors.body}</small>}
                    </label>
                    <button className="account-primary" type="submit" disabled={form.processing}>إرسال الرسالة</button>
                </form>
            </section>
            <section className="account-section" aria-labelledby="support-list">
                <div className="account-section-heading"><div><span>المحادثات</span><h2 id="support-list">رسائلك السابقة</h2></div></div>
                {threads.length === 0 ? <p>لم ترسلي أي رسالة بعد.</p> : <ul className="support-threads">
                    {threads.map((thread) => <li key={thread.id}>
                        <Link href={`/app/support/${thread.id}`}>
                            <strong>{thread.subject}</strong>
                            <span>{thread.topicLabel} · {thread.status === 'closed' ? 'مغلقة' : 'مفتوحة'}</span>
                            <time dateTime={thread.lastMessageAt}>{formatTime(thread.lastMessageAt)}</time>
                            {thread.unread && <em className="account-badge">رد جديد</em>}
                        </Link>
                    </li>)}
                </ul>}
            </section>
        </div>
    </AppShell>;
}
