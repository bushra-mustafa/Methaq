/** Aggregate, non-personal events only. Never add a name that carries a person, a guest or an invitation. */
export type AnalyticsEvent = 'cta_register' | 'template_start' | 'browse_templates';

export interface AnalyticsConfig {
    enabled: boolean;
    domain: string | null;
    script: string | null;
    exclude: string | null;
}

declare global {
    interface Window {
        plausible?: (event: string) => void;
    }
}

const SCRIPT_ID = 'methaq-analytics';

/** Loads the cookieless counter, once, and only when the server says analytics are configured. */
export function enableAnalytics(config: AnalyticsConfig): void {
    if (!config.enabled || !config.domain || !config.script || document.getElementById(SCRIPT_ID)) {
        return;
    }

    const script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.defer = true;
    script.src = config.script;
    script.dataset.domain = config.domain;
    if (config.exclude) {
        script.dataset.exclude = config.exclude;
    }
    document.head.appendChild(script);
}

/** Counts a public conversion step; does nothing unless the counter was loaded after consent. */
export function trackEvent(name: AnalyticsEvent): void {
    if (document.getElementById(SCRIPT_ID) && typeof window.plausible === 'function') {
        window.plausible(name);
    }
}
