#!/usr/bin/env node
// Stops the shared headless Chrome that builds reuse (the next build starts it again on demand).
//   node .ai/skills/methaq-card-designer/scripts/chrome-stop.mjs
import { stopSharedChrome } from './lib/chrome.mjs';

console.log((await stopSharedChrome()) ? 'shared Chrome stopped' : 'no shared Chrome was running');
