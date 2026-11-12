import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface AdminTemplateFeaturedFormProps {
    id: string;
    isFeatured: boolean;
}

/** Lets the admin choose which templates appear in «قوالب مختارة من ميثاق» on the home page. */
export function AdminTemplateFeaturedForm({ id, isFeatured }: AdminTemplateFeaturedFormProps) {
    const form = useForm({ is_featured: !isFeatured, reason: '' });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.patch(`/app/admin/library/template/${id}/featured`, {
            preserveScroll: true,
            onSuccess: () => form.reset('reason'),
        });
    }

    return <form className="admin-library-status" onSubmit={submit}>
        <label><span>سبب {isFeatured ? 'إزالته من' : 'عرضه في'} الرئيسية</span><input name="reason" value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} maxLength={500} required /></label>
        {form.errors.reason && <small role="alert">{form.errors.reason}</small>}
        <button className={isFeatured ? 'is-deactivate' : 'is-activate'} type="submit" disabled={form.processing}>{isFeatured ? 'إزالة من الرئيسية' : 'عرض في الرئيسية'}</button>
    </form>;
}
