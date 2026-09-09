import { useCallback, useState } from 'react';
import { commitHistory, createHistory, redoHistory, undoHistory } from '../Services/editorHistory';

export function useHistory<T>(initialValue: T) {
    const [history, setHistory] = useState(() => createHistory(initialValue));

    const commit = useCallback((update: (current: T) => T): void => {
        setHistory((current) => commitHistory(current, update));
    }, []);

    const undo = useCallback((): void => {
        setHistory(undoHistory);
    }, []);

    const redo = useCallback((): void => {
        setHistory(redoHistory);
    }, []);

    const reset = useCallback((value: T): void => {
        setHistory(createHistory(value));
    }, []);

    return {
        value: history.present,
        commit,
        undo,
        redo,
        reset,
        canUndo: history.past.length > 0,
        canRedo: history.future.length > 0,
    };
}
