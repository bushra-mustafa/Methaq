import { useMemo, useState } from 'react';
import { categoryLabels } from '../../../Types/Library';
import type { EventCategory } from '../../../Types/Library';
import type { EditorAsset } from '../../../Types/Editor';
import { SUGGESTION_CREDIT, suggestionContent, suggestionsFor, type SuggestionKind, type TextSuggestion } from '../Services/textSuggestions';

interface Props {
    category: EventCategory;
    fonts: EditorAsset[];
    onInsert: (suggestion: TextSuggestion, font: EditorAsset) => void;
}

const kinds: Array<{ value: SuggestionKind; label: string }> = [
    { value: 'verse', label: 'آيات' },
    { value: 'phrase', label: 'عبارات' },
];

const verseFontSlugs = ['font-amiri-quran', 'font-amiri'];
const phraseFontSlugs = ['font-amiri', 'font-el-messiri'];

export function pickSuggestionFont(kind: SuggestionKind, fonts: EditorAsset[]): EditorAsset | null {
    const preferred = kind === 'verse' ? verseFontSlugs : phraseFontSlugs;
    for (const slug of preferred) {
        const font = fonts.find((candidate) => candidate.slug === slug);
        if (font) return font;
    }

    return fonts[0] ?? null;
}

export function EditorSuggestionsPanel({ category, fonts, onInsert }: Props) {
    const [selectedCategory, setSelectedCategory] = useState<EventCategory>(category);
    const [kind, setKind] = useState<SuggestionKind>('verse');
    const items = useMemo(() => suggestionsFor(selectedCategory, kind), [selectedCategory, kind]);
    const font = pickSuggestionFont(kind, fonts);

    return <section className="editor-panel-section" aria-labelledby="suggestions-title">
        <header><span>03</span><div><h2 id="suggestions-title">آيات وعبارات جاهزة</h2><p>اختاري نصاً مناسباً لمناسبتك، يُضاف إلى البطاقة وتعدّلينه كما تشائين.</p></div></header>
        <label className="editor-suggestion-category"><span>المناسبة</span>
            <select value={selectedCategory} onChange={(event) => setSelectedCategory(event.target.value as EventCategory)}>
                {(Object.keys(categoryLabels) as EventCategory[]).map((value) => <option key={value} value={value}>{categoryLabels[value]}</option>)}
            </select>
        </label>
        <div className="editor-suggestion-kinds" role="group" aria-label="نوع النص">
            {kinds.map((item) => <button key={item.value} type="button" aria-pressed={kind === item.value} onClick={() => setKind(item.value)}>{item.label}</button>)}
        </div>
        {items.length === 0 && <p className="editor-empty">لا توجد نصوص لهذا القسم بعد.</p>}
        <ul className="editor-suggestion-list">
            {items.map((item) => <li key={item.id}>
                <button type="button" disabled={!font} onClick={() => font && onInsert(item, font)} aria-label={`إضافة: ${item.text}`}>
                    <span className="editor-suggestion-text" style={{ fontFamily: font?.fontFamily ?? undefined }} dir="rtl">{suggestionContent(item)}</span>
                    <small>{item.kind === 'verse' ? (item.partial ? 'جزء من الآية · ' : '') + 'إضافة إلى البطاقة' : 'إضافة إلى البطاقة'}</small>
                </button>
            </li>)}
        </ul>
        {kind === 'verse' && <p className="editor-suggestion-credit">{SUGGESTION_CREDIT}</p>}
    </section>;
}
