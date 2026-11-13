import { useForm } from "@inertiajs/react";
import type { ChangeEvent, FormEvent } from "react";

type AuthFormData = Record<string, string | boolean>;
type TextKeys<T extends AuthFormData> = {
    [K in keyof T]: T[K] extends string ? K : never;
}[keyof T] &
    string;

/** The slice of Inertia's form helper the auth pages use, typed by their own fields. */
interface AuthForm<T extends AuthFormData> {
    data: T;
    errors: Partial<Record<keyof T, string>>;
    processing: boolean;
    setData<K extends keyof T & string>(key: K, value: T[K]): void;
    reset(...fields: (keyof T & string)[]): void;
    post(url: string, options?: { onFinish?: () => void; onError?: () => void }): void;
}

/** After a failed submit, move focus to the first field the server rejected so the error is read at once. */
function focusFirstInvalidField(): void {
    window.setTimeout(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus(), 60);
}

/**
 * The state every auth page repeats: an Inertia form that posts to one URL and clears
 * the fields that must never survive a round trip (passwords, codes).
 * `bind(name)` returns the props a text field needs, so a page only lists its fields.
 */
export default function useAuthForm<T extends AuthFormData>(
    url: string,
    initial: T,
    clearOnFinish: (keyof T & string)[] = [],
) {
    const form = useForm<AuthFormData>(initial) as unknown as AuthForm<T>;

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        form.post(url, {
            onFinish: () => form.reset(...clearOnFinish),
            onError: focusFirstInvalidField,
        });
    };

    const bind = (name: TextKeys<T>) => ({
        name,
        value: form.data[name] as string,
        error: form.errors[name],
        onChange: (event: ChangeEvent<HTMLInputElement>): void =>
            form.setData(name, event.target.value as T[typeof name]),
    });

    return { form, submit, bind, processing: form.processing };
}
