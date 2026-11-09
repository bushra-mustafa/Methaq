import test from 'node:test';
import assert from 'node:assert/strict';
import { createIvoryProposal, localMovement, moveProposalLayer, parseProposal, updateProposalPart } from '../../resources/js/Domains/Editor/Proposals/Ivory/Services/proposalDocument.ts';
import { descendants } from '../../resources/js/Domains/Editor/Proposals/Ivory/Services/proposalCatalog.ts';
import { commitHistory, createHistory, undoHistory } from '../../resources/js/Domains/Editor/Services/editorHistory.ts';

test('every independent element and custom settings roundtrip through the draft contract', () => {
    const document = createIvoryProposal();
    const updated = updateProposalPart(document, 'signature', (part) => part.kind === 'text' ? { ...part, visible: false, content: '', x: 40 } : part);
    assert.deepEqual(parseProposal(JSON.parse(JSON.stringify(updated))), updated);
    assert.equal(document.parts.find((part) => part.id === 'signature')?.visible, true);
    assert.equal(updated.parts.find((part) => part.id === 'signature')?.visible, false);
    assert.equal(descendants('envelope').length, 5);
});

test('moving a parent preserves the local properties of its independent children and undo', () => {
    const document = createIvoryProposal();
    const changed = commitHistory(createHistory(document), (doc) => updateProposalPart(doc, 'envelope', (part) => ({ ...part, x: 80, scale: 2, rotation: 90 })));
    assert.deepEqual(changed.present.parts.filter((part) => descendants('envelope').slice(1).includes(part.id)), document.parts.filter((part) => descendants('envelope').slice(1).includes(part.id)));
    const movement = localMovement(changed.present, 'envelope-flap', 10, 20);
    assert.ok(Math.abs(movement.x - 10) < .001);
    assert.ok(Math.abs(movement.y + 5) < .001);
    assert.deepEqual(undoHistory(changed).present, document);
});

test('a group layer move reorders all its children without losing any layer', () => {
    const document = createIvoryProposal();
    const moved = moveProposalLayer(document, 'envelope', 1);
    assert.deepEqual([...moved.layerOrder].sort(), [...document.layerOrder].sort());
    const members = descendants('envelope').filter((id) => moved.layerOrder.includes(id));
    const positions = members.map((id) => moved.layerOrder.indexOf(id));
    assert.equal(Math.max(...positions) - Math.min(...positions), members.length - 1);
    assert.ok(moved.layerOrder.indexOf('title') < Math.min(...positions));
});

test('malformed image references and layer lists are rejected before replacing a draft', () => {
    const document = createIvoryProposal();
    const image = updateProposalPart(document, 'architecture', (part) => part.kind === 'image' ? { ...part, source: 'javascript:alert(1)' } : part);
    assert.throws(() => parseProposal(image));
    assert.throws(() => parseProposal({ ...document, layerOrder: document.layerOrder.map(() => 'title') }));
    assert.throws(() => parseProposal(updateProposalPart(document, 'names', (part) => ({ ...part, opacity: Infinity }))));
});
