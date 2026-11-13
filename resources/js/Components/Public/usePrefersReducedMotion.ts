import { useEffect, useState } from "react";

const QUERY = "(prefers-reduced-motion: reduce)";

/** True when the visitor asked the system to reduce motion; updates live. */
export default function usePrefersReducedMotion(): boolean {
    const [reduced, setReduced] = useState(
        () => window.matchMedia(QUERY).matches,
    );

    useEffect(() => {
        const media = window.matchMedia(QUERY);
        const update = (): void => setReduced(media.matches);
        media.addEventListener("change", update);

        return () => media.removeEventListener("change", update);
    }, []);

    return reduced;
}
