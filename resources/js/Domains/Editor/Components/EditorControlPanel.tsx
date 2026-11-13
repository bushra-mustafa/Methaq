import type { ReactNode } from 'react';
import type { EditorPanel } from '../../../Types/Editor';

interface Props {
    tab: 'elements' | 'properties';
    tool: EditorPanel;
    hasSelection: boolean;
    onTab: (tab: 'elements' | 'properties') => void;
    onTool: (tool: EditorPanel) => void;
    children: ReactNode;
}

const tools: Array<{ value: EditorPanel; label: string }> = [
    { value: 'layers', label: 'عناصر البطاقة' },
    { value: 'elements', label: 'إضافة زخارف' },
    { value: 'text', label: 'إضافة نص' },
    { value: 'phrases', label: 'آيات وعبارات' },
    { value: 'colors', label: 'الألوان' },
    { value: 'material', label: 'الخامة' },
];

export function EditorControlPanel({ tab, tool, hasSelection, onTab, onTool, children }: Props) {
    return <>
        <div className="editor-panel-tabs" role="group" aria-label="لوحة التحرير">
            <button type="button" aria-pressed={tab === 'elements'} onClick={() => onTab('elements')}>العناصر</button>
            <button type="button" aria-pressed={tab === 'properties'} disabled={!hasSelection} onClick={() => onTab('properties')}>التفاصيل</button>
        </div>
        {tab === 'elements' && <>
            <p className="editor-panel-help">اختاري عنصراً من بطاقتك لتعديل تفاصيله، أو أضيفي لمستك من المكتبة.</p>
            <div className="editor-tool-picker" role="group" aria-label="أدوات البطاقة">
                {tools.map(({ value, label }) => <button key={value} type="button" aria-pressed={tool === value} onClick={() => onTool(value)}>{label}</button>)}
            </div>
        </>}
        <div className="editor-panel-content">{children}</div>
    </>;
}
