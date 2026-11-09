import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { DesignProposal, ProposalSnapshot } from '../../../../../Types/DesignProposal';
import { parseProposal, parseProposalSnapshot } from '../Services/proposalDocument';

export function useProposalDraft(document: DesignProposal, initialRevision: number, onRestore: (document: DesignProposal) => void) {
    const latest = useRef(document);
    latest.current = document;
    const saved = useRef(initialRevision === 0 ? '' : JSON.stringify(document));
    const revision = useRef(initialRevision);
    const busy = useRef(false);
    const alive = useRef(true);
    const request = useRef<AbortController | null>(null);
    const [status, setStatus] = useState(initialRevision > 0 ? 'تم حفظ المسودة' : 'نجهّز مسودتك الخاصة');
    const [saving, setSaving] = useState(false);
    const [conflict, setConflict] = useState<ProposalSnapshot | null>(null);
    const [savedVersion, setSavedVersion] = useState(initialRevision);
    const [error, setError] = useState('');
    const dirty = JSON.stringify(document) !== saved.current;
    const save = useCallback(async () => {
        if (busy.current || conflict) return;
        busy.current = true; setSaving(true); setError(''); setStatus('جارٍ حفظ المسودة…');
        const controller = new AbortController();
        request.current = controller;
        try {
            const target = parseProposal(latest.current);
            const response = await axios.patch<unknown>('/app/proposals/ivory-wedding', { document: target, expectedRevision: revision.current }, { signal: controller.signal, headers: { Accept: 'application/json' } });
            const snapshot = parseProposalSnapshot(response.data);
            if (!alive.current || !snapshot.document) return;
            saved.current = JSON.stringify(snapshot.document);
            revision.current = snapshot.revision; setSavedVersion(snapshot.revision);
            setStatus(JSON.stringify(latest.current) === saved.current ? 'تم حفظ المسودة' : 'تعديلات بانتظار الحفظ');
        } catch (exception: unknown) {
            if (!alive.current || axios.isCancel(exception)) return;
            if (axios.isAxiosError<{ snapshot?: unknown; errors?: Record<string, string[]> }>(exception) && exception.response?.status === 409) {
                setConflict(parseProposalSnapshot(exception.response.data.snapshot));
                setStatus('توجد نسخة أحدث في جلسة أخرى');
            } else {
                const validation = axios.isAxiosError<{ errors?: Record<string, string[]> }>(exception) ? exception.response?.data.errors?.document?.[0] : undefined;
                setError(validation ?? 'تعذّر حفظ المسودة. تعديلاتك ما زالت هنا؛ أعيدي المحاولة أو نزّلي نسخة.');
                setStatus('لم يتم الحفظ');
            }
        } finally {
            busy.current = false;
            if (alive.current) setSaving(false);
        }
    }, [conflict]);
    useEffect(() => {
        if (!dirty || conflict || error || saving) return;
        if (savedVersion === 0 && JSON.stringify(document) === saved.current) return;
        const timer = setTimeout(() => { void save(); }, 1200);
        return () => clearTimeout(timer);
    }, [document, dirty, save, conflict, error, saving, savedVersion]);
    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => { if (JSON.stringify(latest.current) !== saved.current) event.preventDefault(); };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, []);
    useEffect(() => { alive.current = true; return () => { alive.current = false; request.current?.abort(); }; }, []);
    const restoreServer = () => {
        if (!conflict?.document) return;
        saved.current = JSON.stringify(conflict.document); revision.current = conflict.revision;
        setSavedVersion(conflict.revision); setConflict(null); setError(''); setStatus('تم تحميل نسخة الخادم'); onRestore(conflict.document);
    };
    return { status: dirty && !saving && !error && !conflict && savedVersion > 0 ? 'تعديلات بانتظار الحفظ' : status, saving, dirty, save, error, conflict, restoreServer };
}
