# Design rules

What a Methaq template must be, and how to judge it. The build enforces the measurable parts; the rest is your eye.

The bar: wedding-stationery studio quality, not clip-art. The owner is picky: ivory and gold luxury, real craft, calm Arabic typography, nothing generic. Customers open the template in the editor, replace the sample names, date and venue, and share a link, so it must look **premium and finished at first glance** and stay correct when they edit it.

## The card

- Canvas 1080×1920 (9:16), all positions in canvas pixels. Keep important text 60px or more from the edges (the build warns under 48px).
- Layers, bottom first; about 25 is plenty:
  - `image`: an SVG asset, which the editor draws as its PNG raster. One full-canvas frame (1080×1920, locked) plus 0–3 extras (divider, medallion, corner, icons).
  - `text`: an editable Fabric `Textbox`. The customer edits words, size and colour roles.
  - `verse`: Quranic text, only an id from `TemplateScripture` (`context.mjs` lists the ids for each occasion). Never type Quran text. A typed basmala is ordinary text and is fine.
  - `shape`: a plain rectangle, ellipse or line in palette colours. Use it sparingly; art belongs in SVG.

## Palette

Six roles: `background`, `surface`, `primaryText`, `secondaryText`, `accent`, `effect` (`effect` is the sparkle and smoke colour in the animated scene). Text layers and the canvas background should use roles, so the customer can recolour the card. SVG art has fixed colours, so design it to still look acceptable when the palette changes: neutral, tasteful art.

## Fonts

Only the nine library fonts (`context.mjs` prints the live list):

| slug | use |
|---|---|
| font-aref-ruqaa | ruqaa calligraphy: hero names, occasion words (Arabic) |
| font-amiri | elegant naskh: names, invitation lines, closing lines (Arabic and Latin) |
| font-reem-kufi | geometric kufi, weights 400–700: modern or formal headings |
| font-el-messiri | contemporary display, weights 400–700: celebratory headings |
| font-noto-arabic | clean sans: dates, venues, small details (weight 500 reads as 400) |
| font-amiri-quran | verses only (the editor gives it line height 1.7) |
| font-pinyon | Latin copperplate script, Latin text only |
| font-cormorant | Latin high-contrast serif: dates, English lines |
| font-manrope | Latin geometric sans |

Only Reem Kufi and El Messiri have real weight axes. The others ignore weights under 600 and fake bold above, so keep them at 400 or 500. There is no letter spacing. Latin-only fonts carry only Latin text; never fake Arabic with them.

## How text behaves (the preview reproduces the editor)

- A text box is a Fabric `Textbox`: the width is kept and the height follows the text. It wraps at spaces only, so a single word wider than the box overflows it.
- Line pitch is `size × 1.13 × lineHeight`, with lineHeight 1.25 (1.7 for font-amiri-quran). Total height ≈ `1.13 · size · (1 + 1.25 · (lines − 1))`.
- `align: center` is the default and what almost every card uses.
- Put `expectLines` on layers whose wrapping matters (a name that must stay on one line, a verse meant to take two lines), so an accidental re-wrap fails the build.
- Make boxes wide enough for about 25% more text than the sample, because customers type their own names.
- Declare `h` at least as tall as the rendered text; the build warns where the editor would show an overflow warning.

## Legibility

- Body and detail text 32px or more (28 at the very least), verse 30 or more, hero names 96–190px. The names and the occasion line are the visual hero.
- Contrast is measured per glyph pixel: aim for 4.5:1 for small text and 3:1 for large display text.
- Give text its own calm zone; never set it over busy ornament.

## Sample copy

The customer replaces it, but it must be real and tasteful.

- Names common in Libya: محمد، علي، أحمد، يوسف، عمر، آدم، سالم، مفتاح، عبدالسلام، فتحي، خالد / ملاك، نوارة، مريم، خديجة، سعاد، ريم، ليان، أسماء، سلمى، فاطمة، هنية، عائشة.
- Places: طرابلس، بنغازي، مصراتة، سبها، غدامس، الزاوية، البيضاء، درنة، طبرق، الخمس. Halls: قاعة الياسمين، قاعة ميثاق، بيت العائلة…
- Dates in Latin digits with a 2027 date, for example `14/8/2027 · الثامنة مساءً`. Don't spell out months.
- Warm, natural, Libyan-friendly wording: «أفراحنا تزدان بحضوركم»، «فرحتنا ما تكمل إلا بدعواتكم وحضوركم». `context.mjs <category>` lists the lines the editor already suggests: use them for tone, but don't copy the exact lines sibling templates use.
- Include one `verse` layer whose id suits the occasion, placed where it reads well. Its «(سورة …)» source line is part of the text.
- Text the user wrote in the idea goes on the card as written (fix only obvious typos, and say so).

## Scene: the animated envelope before the card

Choose `envelope.style`, `sealStyle`, the paper, lining and seal colours (role names or hex), and a monogram of at most 12 characters («ي م» or «S & O»). Sparkle is on by default; add smoke for moody or dark designs. Use a `backdrop` only if it truly matches the design. Leave `audio` null unless it is a henna card (`audio-henna-celebration`); owners pick their music in the editor. The envelope should belong to the same design: a paper colour that harmonises with the card but stands apart from the page background.

## Card material (optional)

`canvas.material` = `cardstock | cotton | kraft | linen | wood` with an intensity of 0–1 lays a real paper texture under all layers. It shows only where the frame SVG is transparent, so for a tactile paper look leave the paper area unpainted and let `background` and the material show through.

## Distinctiveness

- Look at the contact sheet (`context.mjs --sheet`) before drawing. The new card must not read as a variant of an existing template, and must not drift towards the generic centred-frame-with-gold-divider look the library already has plenty of.
- Pick a composition archetype on purpose and write it in the brief, for example: an arch or mihrab window holding the names; an asymmetric botanical corner with a free text column; a full-bleed pattern with one calm panel; a top tab or ribbon heading; horizontal bands; a central medallion; a pure typographic card with a single ornament; a border-only minimal card on a textured material.
- One signature motif done at high quality beats many weak ones.

## Cultural safety

- No human figures, faces or body parts: no henna hands, no couples, no brides. No animals except abstract birds, swans or doves.
- No musical instruments, alcohol, crosses or other faiths' symbols, flags, brands or logos, rainbows.
- Fine: crescents, stars, lanterns, arches, geometry, botanicals, abstract calligraphic lines, ribbons, balloons, clouds.
- Draw your own artwork. Never trace or embed images, and use no external assets. No lettering inside the art.
- If the idea asks for something here (a bride's silhouette, henna hands), say so and offer the nearest allowed alternative (a henna-pattern border, a veil-like lace arch).

## Art-direction review (every round)

Look at `preview.png` whole and in crops, then answer honestly:

1. **Hierarchy**: are the names or the occasion the hero, and is the reading order obvious from top to bottom?
2. **Balance and spacing**: even margins, no crowded zone, no dead empty zone; centred things exactly on x = 540.
3. **Ornament**: smooth curves without kinks, consistent stroke weights, symmetry where it is meant, crisp details in the crops, nothing that looks programmatic or accidental.
4. **Colour**: harmonious palette, believable metal gradients, enough contrast for every line of text.
5. **Text**: Arabic letters join correctly and read right-to-left; names, date and venue are legible in the full-card preview; no overflow or awkward wraps; verse marks are not clipped; longer replacement names still fit. Compare the rendered copy with the brief so user-provided wording is not silently changed.
6. **Editability**: no text trapped on ornament; boxes that survive real names.
7. **Scene**: envelope, seal and effects belong to this design.
8. **The shop test**: would it sell in a stationery shop next to professional work? If not, keep going.

Write the main issues and fixes of each round in `brief.md`. Stop after 8 rounds if it is not converging, and tell the user what is weak instead of polishing forever.
