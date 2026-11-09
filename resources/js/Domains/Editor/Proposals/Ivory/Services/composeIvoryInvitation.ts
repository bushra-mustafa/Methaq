import type { DesignProposal } from '../../../../../Types/DesignProposal';
import { ArchitecturalBackground } from '../Components/ArchitecturalBackground';
import { InvitationPaper } from '../Components/InvitationPaper';
import { PaperOrnament } from '../Components/PaperOrnament';
import { IvoryEnvelope } from '../Components/IvoryEnvelope';
import { InvitationTitle } from '../Components/InvitationTitle';
import { CoupleNames } from '../Components/CoupleNames';
import { InvitationMessage } from '../Components/InvitationMessage';
import { VenueDetails } from '../Components/VenueDetails';
import { DateAndTime } from '../Components/DateAndTime';
import { ClosingAndSignature } from '../Components/ClosingAndSignature';
import { SwanPair } from '../Components/SwanPair';
import { ProposalDrawing } from './ProposalDrawing';

export async function composeIvoryInvitation(document: DesignProposal): Promise<{ drawing: ProposalDrawing; height: number }> {
    const drawing = new ProposalDrawing(document);
    try {
        const architecture = await ArchitecturalBackground(drawing);
        const paperTop = architecture ? architecture * .62 : 0;
        const envelopeTop = architecture ? architecture * .39 : 45;
        const envelopeHeight = await IvoryEnvelope(drawing, envelopeTop);
        await PaperOrnament(drawing, paperTop);
        let cursor = Math.max(paperTop + 90, envelopeHeight ? envelopeTop + envelopeHeight + 60 : architecture + 40);
        cursor += InvitationTitle(drawing, cursor);
        cursor += CoupleNames(drawing, cursor);
        cursor += InvitationMessage(drawing, cursor);
        const both = drawing.visible('venue') && drawing.visible('schedule');
        const details = await Promise.all([VenueDetails(drawing, cursor, both ? 635 : 450), DateAndTime(drawing, cursor, both ? 265 : 450)]);
        cursor += Math.max(...details);
        const closing = ClosingAndSignature(drawing, cursor + (drawing.visible('closing') ? 35 : 0));
        cursor += closing + (closing ? 35 : 0);
        cursor += await SwanPair(drawing, cursor);
        const boundsBottom = Math.max(0, ...drawing.objects.filter((part) => !['architecture', 'ornament'].includes(part.id)).map(({ object }) => { const box = object.getBoundingRect(); return box.top + box.height; }));
        const height = Math.ceil(Math.max(cursor + 40, boundsBottom + 40, 900));
        if (height > 7200) throw new Error('الدعوة أصبحت طويلة جداً. قللي حجم النص أو زيدي عرضه قبل المعاينة.');
        await InvitationPaper(drawing, paperTop, height - paperTop);
        drawing.objects.sort((a, b) => document.layerOrder.indexOf(a.id) - document.layerOrder.indexOf(b.id));
        return { drawing, height };
    } catch (error: unknown) {
        drawing.objects.forEach(({ object }) => object.dispose());
        throw error;
    }
}
