import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import { FormField } from '../../../Domains/Users/Components/FormField';
import type { EventTemplateOption, SelectOption } from '../../../Types/EventDTO';
import type { EventCategory } from '../../../Types/Library';
import '../../../../css/events.css';

interface CreateEventProps {
    templates: EventTemplateOption[];
    categories: SelectOption<EventCategory>[];
    timezones: SelectOption<string>[];
    selectedTemplateSlug: string | null;
}

interface CreateEventForm {
    title: string;
    category: EventCategory;
    template_id: string | null;
    event_date: string;
    timezone: string;
}

export default function CreateEvent({ templates, categories, timezones, selectedTemplateSlug }: CreateEventProps) {
    const initialTemplate = templates.find((template) => template.slug === selectedTemplateSlug) ?? null;
    const form = useForm<CreateEventForm>({
        title: '',
        category: initialTemplate?.category ?? 'wedding',
        template_id: initialTemplate?.id ?? null,
        event_date: '',
        timezone: 'Africa/Tripoli',
    });

    function selectTemplate(template: EventTemplateOption | null): void {
        form.setData('template_id', template?.id ?? null);
        if (template) form.setData('category', template.category);
        form.clearErrors('template_id');
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/app/events', { preserveScroll: true });
    }

    return <AppShell eyebrow="مناسبة جديدة" title="اختاري البداية وأضيفي الموعد">
        <Head title="إنشاء مناسبة" />
        <form className="event-create-form" onSubmit={submit} noValidate>
            <section className="event-form-section" aria-labelledby="event-basics-title"><header><span>01</span><div><h2 id="event-basics-title">بيانات المناسبة</h2><p>هذه البيانات تظهر في لوحتك، ويمكن تعديل محتوى البطاقة لاحقاً.</p></div></header><div className="event-fields-grid">
                <FormField label="عنوان المناسبة" name="title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder="مثلاً: حفل زفاف سارة وعمر" required autoFocus error={form.errors.title} />
                <label className="account-field" htmlFor="event-category"><span>نوع المناسبة</span><select id="event-category" value={form.data.category} onChange={(event) => form.setData('category', event.target.value as EventCategory)} disabled={form.data.template_id !== null}>{categories.map((category) => <option key={category.value} value={category.value}>{category.label}</option>)}</select>{form.data.template_id && <small className="event-field-note">يتبع فئة القالب المختار.</small>}{form.errors.category && <small role="alert">{form.errors.category}</small>}</label>
                <FormField label="موعد المناسبة" name="event_date" type="datetime-local" value={form.data.event_date} onChange={(event) => form.setData('event_date', event.target.value)} required error={form.errors.event_date} />
                <label className="account-field" htmlFor="event-timezone"><span>المنطقة الزمنية</span><select id="event-timezone" value={form.data.timezone} onChange={(event) => form.setData('timezone', event.target.value)}>{timezones.map((timezone) => <option key={timezone.value} value={timezone.value}>{timezone.label} · {timezone.value}</option>)}</select>{form.errors.timezone && <small role="alert">{form.errors.timezone}</small>}</label>
            </div></section>

            <section className="event-form-section" aria-labelledby="event-template-title"><header><span>02</span><div><h2 id="event-template-title">بداية التصميم</h2><p>اختيار القالب ينسخ التصميم إلى مناسبتك؛ ويمكن تركيب عناصر أخرى لاحقاً.</p></div></header>{form.errors.template_id && <p className="account-error" role="alert">{form.errors.template_id}</p>}<div className="event-template-picker">
                <button className={form.data.template_id === null ? 'is-selected' : ''} type="button" onClick={() => selectTemplate(null)}><span className="event-blank-preview">م</span><strong>بطاقة فارغة</strong><small>ابدئي من لوحة نظيفة</small></button>
                {templates.map((template) => <button className={form.data.template_id === template.id ? 'is-selected' : ''} type="button" key={template.id} onClick={() => selectTemplate(template)}><img src={template.thumbnailUrl} alt="" /><strong>{template.name}</strong><small>{categories.find((category) => category.value === template.category)?.label}</small></button>)}
            </div></section>

            <footer className="event-form-actions"><Link href="/app">إلغاء</Link><div><p>يُحجز رابط فريد وتُغلق الدعوة بعد 10 أيام من الموعد.</p><button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري إنشاء المناسبة…' : 'إنشاء المسودة'}</button></div></footer>
        </form>
    </AppShell>;
}
