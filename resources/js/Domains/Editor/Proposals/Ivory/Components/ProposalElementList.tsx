import type { DesignProposal, ProposalPartId } from '../../../../../Types/DesignProposal';
import { PROPOSAL_LABELS, PROPOSAL_PARENTS } from '../Services/proposalCatalog';
interface Props { document: DesignProposal; selected: ProposalPartId | null; onSelect: (id: ProposalPartId) => void; onToggle: (id: ProposalPartId) => void }
export function ProposalElementList({ document, selected, onSelect, onToggle }: Props) {
    const tree = (parent?: ProposalPartId) => document.parts.filter((part) => PROPOSAL_PARENTS[part.id] === parent).map((part) =>
        <li key={part.id}>
            <div className={selected === part.id ? 'is-selected' : ''}>
                <button type="button" className="ivory-element-name" onClick={() => onSelect(part.id)} aria-pressed={selected === part.id}><span aria-hidden="true">{part.kind === 'group' ? '⊞' : part.kind === 'text' ? 'ت' : '◇'}</span>{PROPOSAL_LABELS[part.id]}</button>
                <button type="button" className="ivory-visibility" aria-label={(part.visible ? 'إخفاء ' : 'إظهار ') + PROPOSAL_LABELS[part.id]} onClick={() => onToggle(part.id)}>{part.visible ? '◉' : '○'}</button>
            </div>
            {part.kind === 'group' && <ul>{tree(part.id)}</ul>}
        </li>);
    return <nav className="ivory-element-list" aria-label="عناصر الاقتراح"><ul>{tree()}</ul></nav>;
}
