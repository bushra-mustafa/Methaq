export interface HistoryState<T> {
    past: T[];
    present: T;
    future: T[];
}

export const MAXIMUM_HISTORY_ENTRIES = 60;

export function createHistory<T>(initialValue: T): HistoryState<T> {
    return { past: [], present: initialValue, future: [] };
}

export function commitHistory<T>(history: HistoryState<T>, update: (current: T) => T): HistoryState<T> {
    const next = update(history.present);
    if (next === history.present) return history;

    return {
        past: [...history.past, history.present].slice(-MAXIMUM_HISTORY_ENTRIES),
        present: next,
        future: [],
    };
}

export function undoHistory<T>(history: HistoryState<T>): HistoryState<T> {
    const previous = history.past.at(-1);
    if (!previous) return history;

    return { past: history.past.slice(0, -1), present: previous, future: [history.present, ...history.future] };
}

export function redoHistory<T>(history: HistoryState<T>): HistoryState<T> {
    const next = history.future[0];
    if (!next) return history;

    return {
        past: [...history.past, history.present].slice(-MAXIMUM_HISTORY_ENTRIES),
        present: next,
        future: history.future.slice(1),
    };
}
