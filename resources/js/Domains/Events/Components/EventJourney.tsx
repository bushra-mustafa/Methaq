import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { EventDTO } from '../../../Types/EventDTO';
import '../../../../css/journey.css';

export type JourneyStepKey = 'card' | 'envelope' | 'payment' | 'publish';

interface EventJourneyProps {
    event: Pick<EventDTO, 'id' | 'isPaid' | 'status'>;
    current: JourneyStepKey;
    /** False while unsaved edits would be lost by leaving the page. */
    canLeave?: boolean;
}

const steps: { key: JourneyStepKey; label: string; path: (id: string) => string }[] = [
    { key: 'card', label: 'البطاقة', path: (id) => `/app/events/${id}/editor` },
    { key: 'envelope', label: 'الظرف', path: (id) => `/app/events/${id}/presentation` },
    { key: 'payment', label: 'الدفع', path: (id) => `/app/events/${id}/payment` },
    { key: 'publish', label: 'النشر', path: (id) => `/app/events/${id}/presentation#publish-title` },
];

/**
 * The four stages of an invitation, always in the same order: design the card, pick the
 * envelope, pay, publish. It tells the owner where she is and what comes next on every page of the journey.
 */
export function EventJourney({ event, current, canLeave = true }: EventJourneyProps) {
    const currentIndex = steps.findIndex((step) => step.key === current);
    const isDone = (key: JourneyStepKey, index: number): boolean => {
        if (key === 'payment') return event.isPaid;
        if (key === 'publish') return event.status === 'published';

        return index < currentIndex;
    };

    return <nav className="journey" aria-label="مراحل تجهيز الدعوة">
        <ol>
            {steps.map((step, index) => {
                const done = isDone(step.key, index);
                const isCurrent = step.key === current;
                const classes = `journey-step${isCurrent ? ' is-current' : ''}${done ? ' is-done' : ''}`;
                const content = <>
                    <span className="journey-mark" aria-hidden="true">{done ? <Check size={14} strokeWidth={3} /> : index + 1}</span>
                    <span>{step.label}</span>
                </>;

                return <li key={step.key} className={classes}>
                    {isCurrent
                        ? <span aria-current="step">{content}</span>
                        : <Link href={step.path(event.id)} onBefore={() => canLeave} aria-disabled={!canLeave} title={canLeave ? undefined : 'انتظري اكتمال الحفظ قبل الانتقال'}>{content}</Link>}
                </li>;
            })}
        </ol>
    </nav>;
}
