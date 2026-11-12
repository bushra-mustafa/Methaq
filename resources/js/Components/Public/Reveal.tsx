import { useEffect, useRef, useState } from "react";
import type { ElementType, ReactNode } from "react";

interface RevealProps {
    children: ReactNode;
    as?: ElementType;
    /** Stagger in milliseconds, for items revealed one after another. */
    delay?: number;
    className?: string;
}

/**
 * Fades its content up the first time it scrolls into view. Visitors who prefer
 * reduced motion see the content in place, without movement.
 */
export default function Reveal({
    children,
    as: Element = "div",
    delay = 0,
    className = "",
}: RevealProps) {
    const element = useRef<HTMLElement>(null);
    const [revealed, setRevealed] = useState(false);

    useEffect(() => {
        const target = element.current;
        if (!target || revealed) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setRevealed(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: "0px 0px -40px 0px" },
        );
        observer.observe(target);

        return () => observer.disconnect();
    }, [revealed]);

    return (
        <Element
            ref={element}
            data-revealed={revealed}
            style={{ transitionDelay: revealed ? `${delay}ms` : "0ms" }}
            className={`translate-y-6 opacity-0 transition-[opacity,translate] duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] data-[revealed=true]:translate-y-0 data-[revealed=true]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none ${className}`}
        >
            {children}
        </Element>
    );
}
