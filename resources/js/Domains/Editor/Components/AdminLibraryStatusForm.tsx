import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { LibraryResource } from '../../../Types/Library';

interface AdminLibraryStatusFormProps {
    resource: LibraryResource;
    id: string;
    isActive: boolean;
}

export function AdminLibraryStatusForm({ resource, id, isActive }: AdminLibraryStatusFormProps) {
    const form = useForm({ is_active: !isActive, reason: '' });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.patch(`/app/admin/library/${resource}/${id}/status`, {
            preserveScroll: true,
            onSuccess: () => form.reset('reason'),
        });
    }

    return <form className="admin-library-status" onSubmit={submit}>
        <label><span>سبب {isActive ? 'الإيقاف' : 'التفعيل'}</span><input name="reason" value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} maxLength={500} required /></label>
        {form.errors.reason && <small role="alert">{form.errors.reason}</small>}
        <button className={isActive ? 'is-deactivate' : 'is-activate'} type="submit" disabled={form.processing}>{isActive ? 'إيقاف' : 'تفعيل'}</button>
    </form>;
}
