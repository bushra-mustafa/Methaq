import { useCallback, useRef, useState } from 'react';
import type { DesignDocument } from '../../../Types/DesignDocument';
import { parseDesignDocument } from '../Services/DesignDocumentSerializer';
import { useHistory } from './useHistory';

export function useDesignHistory(initial: DesignDocument) {
    const history = useHistory(initial);
    const current = useRef(history.value);
    current.current = history.value;
    const [error, setError] = useState('');
    const commit = useCallback((update: (document: DesignDocument) => DesignDocument): void => {
        try {
            const next = parseDesignDocument(update(current.current));
            current.current = next;
            history.commit(() => next);
            setError('');
        } catch {
            setError('هذا التعديل يتجاوز حدود البطاقة. احتفظنا بآخر تعديل صالح؛ قللي النص أو حجم العنصر وحاولي مجدداً.');
        }
    }, [history.commit]);

    return { ...history, commit, error, dismissError: () => setError('') };
}
