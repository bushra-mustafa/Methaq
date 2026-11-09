import { Head } from '@inertiajs/react';
import { InvitationPlayer } from '../../Domains/Editor/Components/InvitationPlayer';
import { RsvpForm } from '../../Domains/RSVP/Components/RsvpForm';
import type { EditorAsset } from '../../Types/Editor';
import type { Palette } from '../../Types/Palette';
import type { SceneConfig } from '../../Types/SceneConfig';
import '../../../css/invitation.css';
import '../../../css/guest-invitation.css';

interface Props {
    event: { title: string; eventDate: string; timezone: string };
    cardUrl: string;
    palette: Palette;
    scene: SceneConfig;
    assets: EditorAsset[];
}

export default function Show({ event, cardUrl, palette, scene, assets }: Props) {
    return <main className="guest-invitation-page" dir="rtl">
        <Head title={event.title} />
        <InvitationPlayer assets={assets} scene={scene} palette={palette} cardUrl={cardUrl} title={event.title} viewport="desktop" />
        <div className="guest-invitation-details">
            <p className="guest-invitation-date">{event.eventDate}</p>
            <RsvpForm />
        </div>
    </main>;
}
