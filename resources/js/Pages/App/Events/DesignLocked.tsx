import { Head, Link } from '@inertiajs/react';
import { PublishPanel } from '../../../Domains/Events/Components/PublishPanel';
import type { EventDTO } from '../../../Types/EventDTO';
import type { PublishStateDTO } from '../../../Types/PublishDTO';
import '../../../../css/editor.css';
import '../../../../css/presentation.css';

interface DesignLockedProps { event: EventDTO; publish: PublishStateDTO }

/** Shown instead of the editor and the presentation step once the invitation has been published. */
export default function DesignLocked({ event, publish }: DesignLockedProps) {
    return <main className="editor-contract-error" dir="rtl">
        <Head title={`التصميم مقفول · ${event.title}`} />
        <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" />
        <h1>التصميم مقفول بعد النشر</h1>
        <p>دعوتك منشورة، لذلك لم يعد تعديل التصميم متاحاً من حسابك. إن احتجتِ أي تعديل، راسلي فريق ميثاق وسنعدّله ونحدّث الدعوة لك.</p>
        <p><Link href="/app/support">مراسلة فريق ميثاق</Link> · <Link href={`/app/events/${event.id}`}>الرجوع إلى المناسبة</Link></p>
        {publish.state === 'published' && <PublishPanel eventId={event.id} publish={publish} cardUrl={null} revision={event.designRevision} saved />}
    </main>;
}
