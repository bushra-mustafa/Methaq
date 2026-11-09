import { InvitationSeal } from './InvitationSeal';
import type { EnvelopeMotionSceneProps } from '../../../Types/EnvelopeMotion';

interface Props extends Pick<EnvelopeMotionSceneProps, 'names' | 'dateLabel' | 'title' | 'language'> {
    onOpen: () => void;
    disabled: boolean;
}

export function ClassicIvoryEnvelope({ names, dateLabel, title, language, onOpen, disabled }: Props) {
    return <div className="motion-ivory-envelope">
        <div className="motion-ivory-shell" data-envelope-part="shell">
            <div className="motion-ivory-shadow" data-envelope-part="shadow" />
            <div className="motion-ivory-back" />
            <div className="motion-ivory-letter" data-envelope-part="letter" aria-hidden="true">
                <div className="motion-ivory-letter-border">
                    <span className="motion-ivory-letter-kicker">بِكُلِّ الحُبّ</span>
                    <strong className={`motion-ivory-names is-${language}`} dir="auto">{names}</strong>
                    {dateLabel && <small dir="auto">{dateLabel}</small>}
                    <span className="motion-ivory-letter-rule" />
                </div>
            </div>
            <div className="motion-ivory-pocket" aria-hidden="true">
                <svg viewBox="0 0 600 400" preserveAspectRatio="none"><path d="M0 0 300 234 600 0V400H0Z" fill="#EEE8DD" /><path d="M0 400 300 174 600 400Z" fill="#F8F5EE" /><path d="m0 400 300-226 300 226M0 0l232 181M600 0 368 181" fill="none" stroke="#D8CFC0" strokeWidth="1.5" /></svg>
            </div>
            <div className="motion-ivory-flap" data-envelope-part="flap" aria-hidden="true">
                <svg className="motion-ivory-flap-front" viewBox="0 0 600 400" preserveAspectRatio="none"><path d="M0 0H600L306 237Q300 242 294 237Z" fill="#F8F5EE" stroke="#D8CFC0" strokeWidth="1" /><path d="M22 8 300 225 578 8" fill="none" stroke="#E5DDCE" /></svg>
                <svg className="motion-ivory-flap-back" viewBox="0 0 600 400" preserveAspectRatio="none"><path d="M0 0H600L306 237Q300 242 294 237Z" fill="#EEE8DD" stroke="#D8CFC0" strokeWidth="1" /></svg>
            </div>
            <div className="motion-ivory-seal" data-envelope-part="seal"><InvitationSeal style="wax" title={title} disabled={disabled} onOpen={onOpen} /></div>
        </div>
    </div>;
}
