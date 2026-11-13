import type { EventCategory } from '../../../Types/Library';

/**
 * Ready-made wording for the editor: Quran verses and short phrases per occasion.
 * Verse text is the Uthmani text of the Tanzil Project (tanzil.net, CC BY 3.0), copied
 * word for word from the source: partial quotes are whole-word ranges, never edited.
 * Phrases are Methaq copy and need an owner review before launch.
 */
export type SuggestionKind = 'verse' | 'phrase';

export interface TextSuggestion {
    id: string;
    kind: SuggestionKind;
    categories: EventCategory[];
    text: string;
    /** Verses only: «سورة الروم - 21». */
    source: string | null;
    /** Verses only: true when the quote is part of the ayah. */
    partial: boolean;
}

export const SUGGESTION_CREDIT = 'نص الآيات: مشروع تنزيل (tanzil.net)';

export const TEXT_SUGGESTIONS: readonly TextSuggestion[] = [
    { id: 'verse-30-21', kind: 'verse', categories: ["wedding", "henna", "marriage_contract"], text: "وَمِنْ ءَايَٰتِهِۦٓ أَنْ خَلَقَ لَكُم مِّنْ أَنفُسِكُمْ أَزْوَٰجًۭا لِّتَسْكُنُوٓا۟ إِلَيْهَا وَجَعَلَ بَيْنَكُم مَّوَدَّةًۭ وَرَحْمَةً ۚ إِنَّ فِى ذَٰلِكَ لَءَايَٰتٍۢ لِّقَوْمٍۢ يَتَفَكَّرُونَ", source: "سورة الرُّومِ - 21", partial: false },
    { id: 'verse-30-21-p', kind: 'verse', categories: ["wedding", "henna", "marriage_contract"], text: "وَجَعَلَ بَيْنَكُم مَّوَدَّةًۭ وَرَحْمَةً", source: "سورة الرُّومِ - 21", partial: true },
    { id: 'verse-25-74', kind: 'verse', categories: ["wedding", "henna", "marriage_contract", "newborn"], text: "وَٱلَّذِينَ يَقُولُونَ رَبَّنَا هَبْ لَنَا مِنْ أَزْوَٰجِنَا وَذُرِّيَّٰتِنَا قُرَّةَ أَعْيُنٍۢ وَٱجْعَلْنَا لِلْمُتَّقِينَ إِمَامًا", source: "سورة الفُرۡقَانِ - 74", partial: false },
    { id: 'verse-13-23', kind: 'verse', categories: ["wedding", "marriage_contract"], text: "جَنَّٰتُ عَدْنٍۢ يَدْخُلُونَهَا وَمَن صَلَحَ مِنْ ءَابَآئِهِمْ وَأَزْوَٰجِهِمْ وَذُرِّيَّٰتِهِمْ ۖ وَٱلْمَلَٰٓئِكَةُ يَدْخُلُونَ عَلَيْهِم مِّن كُلِّ بَابٍۢ", source: "سورة الرَّعۡدِ - 23", partial: false },
    { id: 'verse-10-58', kind: 'verse', categories: ["henna", "wedding"], text: "قُلْ بِفَضْلِ ٱللَّهِ وَبِرَحْمَتِهِۦ فَبِذَٰلِكَ فَلْيَفْرَحُوا۟ هُوَ خَيْرٌۭ مِّمَّا يَجْمَعُونَ", source: "سورة يُونُسَ - 58", partial: false },
    { id: 'verse-24-32', kind: 'verse', categories: ["marriage_contract"], text: "وَأَنكِحُوا۟ ٱلْأَيَٰمَىٰ مِنكُمْ وَٱلصَّٰلِحِينَ مِنْ عِبَادِكُمْ وَإِمَآئِكُمْ ۚ إِن يَكُونُوا۟ فُقَرَآءَ يُغْنِهِمُ ٱللَّهُ مِن فَضْلِهِۦ ۗ وَٱللَّهُ وَٰسِعٌ عَلِيمٌۭ", source: "سورة النُّورِ - 32", partial: false },
    { id: 'verse-58-11', kind: 'verse', categories: ["graduation"], text: "يَرْفَعِ ٱللَّهُ ٱلَّذِينَ ءَامَنُوا۟ مِنكُمْ وَٱلَّذِينَ أُوتُوا۟ ٱلْعِلْمَ دَرَجَٰتٍۢ", source: "سورة المُجَادلَةِ - 11", partial: true },
    { id: 'verse-20-114', kind: 'verse', categories: ["graduation"], text: "وَقُل رَّبِّ زِدْنِى عِلْمًۭا", source: "سورة طه - 114", partial: true },
    { id: 'verse-39-9', kind: 'verse', categories: ["graduation"], text: "هَلْ يَسْتَوِى ٱلَّذِينَ يَعْلَمُونَ وَٱلَّذِينَ لَا يَعْلَمُونَ", source: "سورة الزُّمَرِ - 9", partial: true },
    { id: 'verse-35-28', kind: 'verse', categories: ["graduation"], text: "إِنَّمَا يَخْشَى ٱللَّهَ مِنْ عِبَادِهِ ٱلْعُلَمَٰٓؤُا۟", source: "سورة فَاطِرٍ - 28", partial: true },
    { id: 'verse-11-88', kind: 'verse', categories: ["project_opening"], text: "تَوْفِيقِىٓ إِلَّا بِٱللَّهِ ۚ عَلَيْهِ تَوَكَّلْتُ وَإِلَيْهِ أُنِيبُ", source: "سورة هُودٍ - 88", partial: true },
    { id: 'verse-62-10', kind: 'verse', categories: ["project_opening"], text: "فَإِذَا قُضِيَتِ ٱلصَّلَوٰةُ فَٱنتَشِرُوا۟ فِى ٱلْأَرْضِ وَٱبْتَغُوا۟ مِن فَضْلِ ٱللَّهِ وَٱذْكُرُوا۟ ٱللَّهَ كَثِيرًۭا لَّعَلَّكُمْ تُفْلِحُونَ", source: "سورة الجُمُعَةِ - 10", partial: false },
    { id: 'verse-17-80', kind: 'verse', categories: ["project_opening"], text: "وَقُل رَّبِّ أَدْخِلْنِى مُدْخَلَ صِدْقٍۢ وَأَخْرِجْنِى مُخْرَجَ صِدْقٍۢ وَٱجْعَل لِّى مِن لَّدُنكَ سُلْطَٰنًۭا نَّصِيرًۭا", source: "سورة الإِسۡرَاءِ - 80", partial: false },
    { id: 'verse-3-38', kind: 'verse', categories: ["newborn"], text: "هُنَالِكَ دَعَا زَكَرِيَّا رَبَّهُۥ ۖ قَالَ رَبِّ هَبْ لِى مِن لَّدُنكَ ذُرِّيَّةًۭ طَيِّبَةً ۖ إِنَّكَ سَمِيعُ ٱلدُّعَآءِ", source: "سورة آلِ عِمۡرَانَ - 38", partial: false },
    { id: 'verse-18-46', kind: 'verse', categories: ["newborn"], text: "ٱلْمَالُ وَٱلْبَنُونَ زِينَةُ ٱلْحَيَوٰةِ ٱلدُّنْيَا ۖ وَٱلْبَٰقِيَٰتُ ٱلصَّٰلِحَٰتُ خَيْرٌ عِندَ رَبِّكَ ثَوَابًۭا وَخَيْرٌ أَمَلًۭا", source: "سورة الكَهۡفِ - 46", partial: false },
    { id: 'verse-14-40', kind: 'verse', categories: ["newborn"], text: "رَبِّ ٱجْعَلْنِى مُقِيمَ ٱلصَّلَوٰةِ وَمِن ذُرِّيَّتِى ۚ رَبَّنَا وَتَقَبَّلْ دُعَآءِ", source: "سورة إِبۡرَاهِيمَ - 40", partial: false },
    { id: 'verse-42-49', kind: 'verse', categories: ["newborn"], text: "لِّلَّهِ مُلْكُ ٱلسَّمَٰوَٰتِ وَٱلْأَرْضِ ۚ يَخْلُقُ مَا يَشَآءُ ۚ يَهَبُ لِمَن يَشَآءُ إِنَٰثًۭا وَيَهَبُ لِمَن يَشَآءُ ٱلذُّكُورَ", source: "سورة الشُّورَىٰ - 49", partial: false },
    { id: 'phrase-wedding-1', kind: 'phrase', categories: ["wedding"], text: "بقلوبٍ يملؤها الحب والفرح، ندعوكم لمشاركتنا أجمل لحظات العمر", source: null, partial: false },
    { id: 'phrase-wedding-2', kind: 'phrase', categories: ["wedding"], text: "يسعدنا حضوركم، ويُتمّ فرحتنا دعاؤكم", source: null, partial: false },
    { id: 'phrase-wedding-3', kind: 'phrase', categories: ["wedding"], text: "ليلةٌ لا تُنسى، وفرحةٌ لا تكتمل إلا بوجودكم", source: null, partial: false },
    { id: 'phrase-wedding-4', kind: 'phrase', categories: ["wedding"], text: "بكل الحب والمودة نتشرف بدعوتكم لحضور حفل زفافنا", source: null, partial: false },
    { id: 'phrase-wedding-5', kind: 'phrase', categories: ["wedding"], text: "حضوركم شرفٌ لنا، ودعاؤكم زادٌ لنا في بداية الطريق", source: null, partial: false },
    { id: 'phrase-wedding-6', kind: 'phrase', categories: ["wedding"], text: "بارك الله لنا ولكم، وجمعنا في كل خير", source: null, partial: false },
    { id: 'phrase-wedding-7', kind: 'phrase', categories: ["wedding"], text: "نبدأ حكايتنا معًا، ويطيب لنا أن تكونوا جزءًا منها", source: null, partial: false },
    { id: 'phrase-wedding-8', kind: 'phrase', categories: ["wedding"], text: "أفراحنا تزدان بحضوركم", source: null, partial: false },
    { id: 'phrase-wedding-9', kind: 'phrase', categories: ["wedding"], text: "الحمد لله الذي جمع بيننا على خير، ونتشرف بحضوركم لتشاركونا أجمل أيام عمرنا.", source: null, partial: false },
    { id: 'phrase-wedding-10', kind: 'phrase', categories: ["wedding"], text: "فرحتنا ما تكمل إلا بدعواتكم وحضوركم، فلا تحرمونا منهم.", source: null, partial: false },
    { id: 'phrase-wedding-11', kind: 'phrase', categories: ["wedding"], text: "بداية حكايتنا حب وستر ورضا، ونتمنى تكونوا معنا فيها.", source: null, partial: false },
    { id: 'phrase-henna-1', kind: 'phrase', categories: ["henna"], text: "ليلة الحنّاء: نقشٌ من الفرح على كفّ العمر", source: null, partial: false },
    { id: 'phrase-henna-2', kind: 'phrase', categories: ["henna"], text: "تعالوا نزيّن الليلة بالحنّاء والغناء والضحكات", source: null, partial: false },
    { id: 'phrase-henna-3', kind: 'phrase', categories: ["henna"], text: "بدعوةٍ عامرة بالحب نتشرف بحضوركم ليلة الحنّاء", source: null, partial: false },
    { id: 'phrase-henna-4', kind: 'phrase', categories: ["henna"], text: "ليلةٌ تفوح بعبق الحنّاء، وتكتمل بوجودكم", source: null, partial: false },
    { id: 'phrase-henna-5', kind: 'phrase', categories: ["henna"], text: "للفرحة طقوسٌ جميلة، وأجملها اجتماعكم حولنا", source: null, partial: false },
    { id: 'phrase-henna-6', kind: 'phrase', categories: ["henna"], text: "نقشٌ وغناءٌ وبهجة، فلا تحرمونا من حضوركم", source: null, partial: false },
    { id: 'phrase-henna-7', kind: 'phrase', categories: ["henna"], text: "حنّاءٌ وفرحٌ وأهلٌ وأحباب", source: null, partial: false },
    { id: 'phrase-henna-8', kind: 'phrase', categories: ["henna"], text: "أدعوكم من كل قلبي لتشاركوني فرحة حفلة حنائي.", source: null, partial: false },
    { id: 'phrase-henna-9', kind: 'phrase', categories: ["henna", "wedding"], text: "ربي رزقني نصيبي وجعله لي خير سند، والحمد لله على نعمته وفضله. نتمنى من ربي يملأ حياتنا حب وبركة وتكون أيامنا مع بعض أحلى وأحلى.", source: null, partial: false },
    { id: 'phrase-henna-10', kind: 'phrase', categories: ["henna"], text: "ليلة الحنّاء ما تكمل إلا بوجودكم، فحيّاكم الله وبيّاكم.", source: null, partial: false },
    { id: 'phrase-henna-11', kind: 'phrase', categories: ["henna"], text: "نقشت الحنّاء على يدي فرحةً، وبقي أن تنقشوا حضوركم في قلبي.", source: null, partial: false },
    { id: 'phrase-henna-12', kind: 'phrase', categories: ["henna"], text: "يا أهلي ويا أحبابي، ليلتي ما تحلى إلا بيكم.", source: null, partial: false },
    { id: 'phrase-marriage_contract-1', kind: 'phrase', categories: ["marriage_contract"], text: "بكل الفخر نتشرف بدعوتكم لحضور عقد القران", source: null, partial: false },
    { id: 'phrase-marriage_contract-2', kind: 'phrase', categories: ["marriage_contract"], text: "ميثاقٌ غليظ ومودةٌ ورحمة، فكونوا معنا شهودًا على الفرح", source: null, partial: false },
    { id: 'phrase-marriage_contract-3', kind: 'phrase', categories: ["marriage_contract"], text: "نجتمع على الخير، ويكتمل الفرح بدعائكم", source: null, partial: false },
    { id: 'phrase-marriage_contract-4', kind: 'phrase', categories: ["marriage_contract"], text: "بحضوركم يُبارَك العقد وتكتمل الفرحة", source: null, partial: false },
    { id: 'phrase-marriage_contract-5', kind: 'phrase', categories: ["marriage_contract"], text: "بسم الله وعلى بركة الله", source: null, partial: false },
    { id: 'phrase-marriage_contract-6', kind: 'phrase', categories: ["marriage_contract"], text: "وذلك بمشيئة الله تعالى", source: null, partial: false },
    { id: 'phrase-marriage_contract-7', kind: 'phrase', categories: ["marriage_contract"], text: "غداءٌ وقراءة الفاتحة", source: null, partial: false },
    { id: 'phrase-marriage_contract-8', kind: 'phrase', categories: ["marriage_contract"], text: "بارك الله لهما وبارك عليهما وجمع بينهما في خير", source: null, partial: false },
    { id: 'phrase-marriage_contract-9', kind: 'phrase', categories: ["marriage_contract"], text: "بنية الستر وبركة الله، نتشرف بدعوتكم لحضور عقد قراننا.", source: null, partial: false },
    { id: 'phrase-marriage_contract-10', kind: 'phrase', categories: ["marriage_contract"], text: "نسأل الله أن يبارك لنا ويجمع بيننا في خير، وندعوكم لتشهدوا فرحتنا.", source: null, partial: false },
    { id: 'phrase-marriage_contract-11', kind: 'phrase', categories: ["marriage_contract"], text: "عقدٌ على الخير والمودة، يزيده حضوركم بركة.", source: null, partial: false },
    { id: 'phrase-graduation-1', kind: 'phrase', categories: ["graduation"], text: "بكل فخر ندعوكم لمشاركتنا فرحة التخرّج", source: null, partial: false },
    { id: 'phrase-graduation-2', kind: 'phrase', categories: ["graduation"], text: "ثمرة سنواتٍ من الجهد والسهر، نقطفها معكم", source: null, partial: false },
    { id: 'phrase-graduation-3', kind: 'phrase', categories: ["graduation"], text: "الحمد لله الذي بلّغنا هذا اليوم، ونسأله التوفيق فيما بعده", source: null, partial: false },
    { id: 'phrase-graduation-4', kind: 'phrase', categories: ["graduation"], text: "حضوركم يزيد فرحتنا بالإنجاز", source: null, partial: false },
    { id: 'phrase-graduation-5', kind: 'phrase', categories: ["graduation"], text: "بداية الطريق، وكل الطرق تبدأ بخطوة", source: null, partial: false },
    { id: 'phrase-graduation-6', kind: 'phrase', categories: ["graduation"], text: "نحتفل معًا بنجاحٍ يُتوّج التعب", source: null, partial: false },
    { id: 'phrase-graduation-7', kind: 'phrase', categories: ["graduation"], text: "مبارك للجميع", source: null, partial: false },
    { id: 'phrase-graduation-8', kind: 'phrase', categories: ["graduation"], text: "الحمد لله الذي وفقني لهذا اليوم، وأتشرف بدعوتكم لتشاركوني فرحة نجاحي.", source: null, partial: false },
    { id: 'phrase-graduation-9', kind: 'phrase', categories: ["graduation"], text: "ثمرة تعب وسهر وصبر، وأحلى ما فيها أنتم، فكونوا معي في يومي.", source: null, partial: false },
    { id: 'phrase-graduation-10', kind: 'phrase', categories: ["graduation"], text: "بفضل الله ثم دعم أهلي وأحبابي وصلت، فشاركوني فرحتي.", source: null, partial: false },
    { id: 'phrase-project_opening-1', kind: 'phrase', categories: ["project_opening"], text: "يسرّنا دعوتكم لحضور حفل افتتاح مشروعنا", source: null, partial: false },
    { id: 'phrase-project_opening-2', kind: 'phrase', categories: ["project_opening"], text: "بداية جديدة، وحلمٌ يتحقق بدعمكم", source: null, partial: false },
    { id: 'phrase-project_opening-3', kind: 'phrase', categories: ["project_opening"], text: "حضوركم يمنح انطلاقتنا بركةً وثقة", source: null, partial: false },
    { id: 'phrase-project_opening-4', kind: 'phrase', categories: ["project_opening"], text: "نتشرف بتشريفكم لقصّ شريط الافتتاح", source: null, partial: false },
    { id: 'phrase-project_opening-5', kind: 'phrase', categories: ["project_opening"], text: "شكرًا لكل من آمن بالفكرة ووقف معنا", source: null, partial: false },
    { id: 'phrase-project_opening-6', kind: 'phrase', categories: ["project_opening"], text: "افتتاحٌ رسمي، وضيافةٌ تليق بكم", source: null, partial: false },
    { id: 'phrase-project_opening-7', kind: 'phrase', categories: ["project_opening"], text: "نبدأ بتوفيق الله، ونمضي بدعمكم", source: null, partial: false },
    { id: 'phrase-project_opening-8', kind: 'phrase', categories: ["project_opening"], text: "بتوفيق الله نفتتح مشروعنا، وندعوكم لتكونوا أول من يشهد بدايتنا.", source: null, partial: false },
    { id: 'phrase-project_opening-9', kind: 'phrase', categories: ["project_opening"], text: "حلمٌ كبر معنا خطوة بخطوة، واليوم نفتح بابه، ونتشرف بحضوركم.", source: null, partial: false },
    { id: 'phrase-project_opening-10', kind: 'phrase', categories: ["project_opening"], text: "شكرًا لكل من دعمنا، فافتتاحنا لا يكتمل إلا بكم.", source: null, partial: false },
    { id: 'phrase-newborn-1', kind: 'phrase', categories: ["newborn"], text: "بقلوبٍ شاكرة ندعوكم للاحتفاء بمولودنا الجديد", source: null, partial: false },
    { id: 'phrase-newborn-2', kind: 'phrase', categories: ["newborn"], text: "أهلًا بقادمٍ جديد ملأ حياتنا فرحًا", source: null, partial: false },
    { id: 'phrase-newborn-3', kind: 'phrase', categories: ["newborn"], text: "وُلدت الفرحة، فتعالوا نحتفل معًا", source: null, partial: false },
    { id: 'phrase-newborn-4', kind: 'phrase', categories: ["newborn"], text: "نحمد الله على نعمته، ونسأله أن يجعله قرّة عين لنا", source: null, partial: false },
    { id: 'phrase-newborn-5', kind: 'phrase', categories: ["newborn"], text: "حضوركم ودعاؤكم أجمل هدية لمولودنا", source: null, partial: false },
    { id: 'phrase-newborn-6', kind: 'phrase', categories: ["newborn"], text: "اللهم بارك فيه واجعله من الصالحين", source: null, partial: false },
    { id: 'phrase-newborn-7', kind: 'phrase', categories: ["newborn"], text: "ضيفٌ جديد وقلبٌ جديد، وبيتٌ يتسع للحب", source: null, partial: false },
    { id: 'phrase-newborn-8', kind: 'phrase', categories: ["newborn"], text: "الحمد لله على عطيّته، رزقنا قرّة عين، وندعوكم لتفرحوا معنا.", source: null, partial: false },
    { id: 'phrase-newborn-9', kind: 'phrase', categories: ["newborn"], text: "وصلنا ضيفنا الصغير، فتعالوا نستقبله بالدعاء والفرحة.", source: null, partial: false },
    { id: 'phrase-newborn-10', kind: 'phrase', categories: ["newborn"], text: "دعواتكم لمولودنا أغلى هدية، وحضوركم يزيدنا سرورًا.", source: null, partial: false },
];

export function suggestionsFor(category: EventCategory, kind: SuggestionKind): TextSuggestion[] {
    return TEXT_SUGGESTIONS.filter((suggestion) => suggestion.kind === kind && suggestion.categories.includes(category));
}

/** The text placed on the card: verses keep their ornamental brackets and name their source. */
export function suggestionContent(suggestion: TextSuggestion): string {
    return suggestion.kind === 'verse' ? `﴿ ${suggestion.text} ﴾\n(${suggestion.source ?? ''})` : suggestion.text;
}
