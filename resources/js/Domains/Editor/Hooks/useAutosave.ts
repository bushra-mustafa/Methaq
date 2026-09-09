import { useCallback, useEffect, useRef, useState } from 'react';
import type { DesignDocument, DesignSnapshot } from '../../../Types/DesignDocument';
import { serializeDesignDocument } from '../Services/DesignDocumentSerializer';
import { DesignSaveConflictError, DesignSaveError, saveDesign } from '../Services/designSaveClient';

export type AutosaveStatus = 'saved' | 'dirty' | 'saving' | 'offline' | 'conflict' | 'error';

interface AutosaveState {
    status: AutosaveStatus;
    message: string;
    revision: number;
    conflict: DesignSnapshot | null;
}

interface UseAutosaveResult extends AutosaveState {
    hasUnsavedChanges: boolean;
    retry(): void;
    keepLocalVersion(): void;
    takeServerVersion(): DesignSnapshot | null;
}

const SAVE_DELAY_MS = 900;

export function useAutosave(endpoint: string, document: DesignDocument, initialRevision: number): UseAutosaveResult {
    const initialSerialized = useRef(serializeDesignDocument(document));
    const latestDocument = useRef(document);
    const savedSerialized = useRef(initialSerialized.current);
    const revision = useRef(initialRevision);
    const activeRequest = useRef<AbortController | null>(null);
    const hasConflict = useRef(false);
    const queued = useRef(false);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const run = useRef<() => void>(() => undefined);
    const [state, setState] = useState<AutosaveState>({ status: 'saved', message: 'تم الحفظ', revision: initialRevision, conflict: null });

    const schedule = useCallback((delay = SAVE_DELAY_MS): void => {
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => run.current(), delay);
    }, []);

    const performSave = useCallback(async (): Promise<void> => {
        if (hasConflict.current) return;
        if (activeRequest.current) {
            queued.current = true;
            return;
        }

        const targetDocument = latestDocument.current;
        const serialized = serializeDesignDocument(targetDocument);
        if (serialized === savedSerialized.current) {
            setState((current) => ({ ...current, status: 'saved', message: 'تم الحفظ', conflict: null }));
            return;
        }
        if (!navigator.onLine) {
            setState((current) => ({ ...current, status: 'offline', message: 'لا يوجد اتصال — التعديلات محفوظة في الصفحة' }));
            return;
        }

        const controller = new AbortController();
        activeRequest.current = controller;
        queued.current = false;
        setState((current) => ({ ...current, status: 'saving', message: 'جارٍ الحفظ…', conflict: null }));
        try {
            const snapshot = await saveDesign(endpoint, { document: targetDocument, expectedRevision: revision.current }, controller.signal);
            hasConflict.current = false;
            revision.current = snapshot.revision;
            savedSerialized.current = serializeDesignDocument(snapshot.document);
            const latestSerialized = serializeDesignDocument(latestDocument.current);
            setState({
                status: latestSerialized === savedSerialized.current ? 'saved' : 'dirty',
                message: latestSerialized === savedSerialized.current ? 'تم الحفظ' : 'تعديلات بانتظار الحفظ',
                revision: snapshot.revision,
                conflict: null,
            });
            if (latestSerialized !== savedSerialized.current) queued.current = true;
        } catch (error: unknown) {
            if (error instanceof DOMException && error.name === 'AbortError') return;
            if (error instanceof DesignSaveConflictError) {
                hasConflict.current = true;
                queued.current = false;
                setState((current) => ({ ...current, status: 'conflict', message: 'يوجد تعديل أحدث من جلسة أخرى', conflict: error.snapshot }));
            } else {
                const message = error instanceof DesignSaveError ? error.message : 'تعذّر حفظ التصميم. احتفظنا بتعديلاتك.';
                setState((current) => ({ ...current, status: navigator.onLine ? 'error' : 'offline', message }));
            }
        } finally {
            if (activeRequest.current === controller) activeRequest.current = null;
            if (queued.current) {
                queued.current = false;
                schedule(0);
            }
        }
    }, [endpoint, schedule]);
    run.current = () => void performSave();

    useEffect(() => {
        latestDocument.current = document;
        const serialized = serializeDesignDocument(document);
        if (serialized === savedSerialized.current) return;
        if (hasConflict.current) return;
        setState((current) => ({ ...current, status: navigator.onLine ? 'dirty' : 'offline', message: navigator.onLine ? 'تعديلات بانتظار الحفظ' : 'لا يوجد اتصال — التعديلات محفوظة في الصفحة' }));
        schedule();
    }, [document, schedule]);

    useEffect(() => {
        const onOnline = (): void => {
            if (serializeDesignDocument(latestDocument.current) !== savedSerialized.current) schedule(0);
        };
        const onOffline = (): void => {
            if (serializeDesignDocument(latestDocument.current) !== savedSerialized.current) {
                setState((current) => ({ ...current, status: 'offline', message: 'لا يوجد اتصال — التعديلات محفوظة في الصفحة' }));
            }
        };
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);

        return () => {
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
        };
    }, [schedule]);

    const hasUnsavedChanges = serializeDesignDocument(document) !== savedSerialized.current;
    useEffect(() => {
        const warnBeforeLeaving = (event: BeforeUnloadEvent): void => {
            if (!hasUnsavedChanges) return;
            event.preventDefault();
        };
        window.addEventListener('beforeunload', warnBeforeLeaving);
        return () => window.removeEventListener('beforeunload', warnBeforeLeaving);
    }, [hasUnsavedChanges]);

    useEffect(() => () => {
        if (timer.current) clearTimeout(timer.current);
        activeRequest.current?.abort();
    }, []);

    const retry = useCallback((): void => schedule(0), [schedule]);
    const keepLocalVersion = useCallback((): void => {
        const conflict = state.conflict;
        if (!conflict) return;
        hasConflict.current = false;
        revision.current = conflict.revision;
        setState({ status: 'dirty', message: 'سيُحفظ عملك فوق نسخة الخادم', revision: conflict.revision, conflict: null });
        schedule(0);
    }, [schedule, state.conflict]);
    const takeServerVersion = useCallback((): DesignSnapshot | null => {
        const conflict = state.conflict;
        if (!conflict) return null;
        hasConflict.current = false;
        revision.current = conflict.revision;
        savedSerialized.current = serializeDesignDocument(conflict.document);
        latestDocument.current = conflict.document;
        setState({ status: 'saved', message: 'تم تحميل نسخة الخادم', revision: conflict.revision, conflict: null });

        return conflict;
    }, [state.conflict]);

    return { ...state, hasUnsavedChanges, retry, keepLocalVersion, takeServerVersion };
}
