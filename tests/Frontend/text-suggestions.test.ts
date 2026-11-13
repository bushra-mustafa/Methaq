import assert from 'node:assert/strict';
import test from 'node:test';
import { SUGGESTION_CREDIT, TEXT_SUGGESTIONS, suggestionContent, suggestionsFor } from '../../resources/js/Domains/Editor/Services/textSuggestions.ts';

const categories = ['wedding', 'henna', 'marriage_contract', 'graduation', 'project_opening', 'newborn'] as const;

test('every occasion offers both verses and phrases', () => {
    for (const category of categories) {
        assert.ok(suggestionsFor(category, 'phrase').length >= 5, `${category} needs phrases`);
        assert.ok(suggestionsFor(category, 'verse').length >= 3, `${category} needs verses`);
    }
});

test('ids are unique and verses always name their source', () => {
    assert.equal(new Set(TEXT_SUGGESTIONS.map((item) => item.id)).size, TEXT_SUGGESTIONS.length);
    for (const item of TEXT_SUGGESTIONS) {
        assert.ok(item.text.trim().length > 0);
        if (item.kind === 'verse') assert.match(item.source ?? '', /^سورة .+ - \d+$/);
        else assert.equal(item.source, null);
    }
});

test('verses are placed inside ornamental brackets with their source; phrases stay plain', () => {
    const verse = TEXT_SUGGESTIONS.find((item) => item.kind === 'verse');
    const phrase = TEXT_SUGGESTIONS.find((item) => item.kind === 'phrase');
    assert.ok(verse && phrase);
    assert.match(suggestionContent(verse), /^﴿ .+ ﴾\n\(سورة .+\)$/);
    assert.equal(suggestionContent(phrase), phrase.text);
    assert.match(SUGGESTION_CREDIT, /tanzil/);
});

test('every text fits the document contract', () => {
    for (const item of TEXT_SUGGESTIONS) assert.ok(Array.from(suggestionContent(item)).length <= 2000);
});
