<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventCategory: string
{
    case Wedding = 'wedding';
    case Henna = 'henna';
    case MarriageContract = 'marriage_contract';
    case Graduation = 'graduation';
    case ProjectOpening = 'project_opening';
    case Newborn = 'newborn';

    public function label(): string
    {
        return match ($this) {
            self::Wedding => 'زفاف',
            self::Henna => 'حنّة',
            self::MarriageContract => 'عقد قران',
            self::Graduation => 'تخرج',
            self::ProjectOpening => 'افتتاح مشروع',
            self::Newborn => 'حفل مولود',
        };
    }

    /** The URL segment of the category's landing page. */
    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public static function fromSlug(string $slug): ?self
    {
        return self::tryFrom(str_replace('-', '_', $slug));
    }

    /** @return array{headline: string, description: string, points: list<string>} Copy for the occasion's landing page. */
    public function pitch(): array
    {
        return match ($this) {
            self::Wedding => [
                'headline' => 'دعوات زفاف إلكترونية تُفتح كالظرف',
                'description' => 'اختاري قالب زفاف، وعدّلي الأسماء والموعد والمكان والألوان، وشاركي دعوتك برابط واحد يفتحه ضيوفك كظرف حقيقي.',
                'points' => ['أسماء العروسين والموعد والمكان بخطوط عربية أنيقة', 'ظرف بختم ذهبي وموسيقى ترحيبية اختيارية', 'رابط واحد تشاركينه في واتساب'],
            ],
            self::Henna => [
                'headline' => 'دعوات حنّة بألوان الليلة وأجوائها',
                'description' => 'قوالب لليلة الحنّة بألوان دافئة وزخارف تراثية، قابلة للتعديل بالكامل، وتصل ضيفاتك بلحظة فتح لا تُنسى.',
                'points' => ['ألوان دافئة وزخارف مستوحاة من التراث', 'خلفيات وحركات خفيفة تناسب الليلة', 'تعديل النصوص والخطوط والعناصر بحرية'],
            ],
            self::MarriageContract => [
                'headline' => 'دعوات عقد قران بوقار وبساطة',
                'description' => 'قوالب هادئة لعقد القران بنقوش هندسية وخطوط عربية، تصلح لدعوة الأهل والأحباب برابط واحد.',
                'points' => ['تصاميم وقورة بنقوش هندسية وأقواس', 'نصوص جاهزة تعدّلينها كما تحبين', 'ظرف يُفتح بختم ميثاق'],
            ],
            self::Graduation => [
                'headline' => 'دعوات حفل التخرج بطابع احتفالي',
                'description' => 'قوالب لحفلات التخرج بإطارات وأشعة احتفالية، تشاركينها مع الأهل والزملاء برابط واحد.',
                'points' => ['إطارات احتفالية وخطوط واضحة', 'ألوان تناسب المناسبة وتتغيّر بسهولة', 'مشاركة سريعة عبر رابط الدعوة'],
            ],
            self::ProjectOpening => [
                'headline' => 'دعوات افتتاح المشاريع بأناقة مهنية',
                'description' => 'دعوة افتتاح لمشروعك: قوالب بلمسة ذهبية وشريط يُقصّ، تدعين بها العملاء والشركاء برابط واحد يحمل هوية مشروعك.',
                'points' => ['قالب قصّ الشريط بألوان رسمية', 'اسم المشروع والموعد والعنوان في مكان بارز', 'رابط يسهل مشاركته مع العملاء والشركاء'],
            ],
            self::Newborn => [
                'headline' => 'دعوات حفل المولود بنعومة الفرح',
                'description' => 'قوالب لاستقبال المولود بهلال ونجوم وسحب ناعمة، تدعين بها العائلة والأحباب لمشاركتك الفرحة.',
                'points' => ['ألوان ناعمة وزخارف مولود لطيفة', 'نصوص ودّية قابلة للتعديل بالكامل', 'رابط واحد يصل كل العائلة'],
            ],
        };
    }

    /** @return list<array{value: string, label: string}> Every category as a select option, in display order. */
    public static function options(): array
    {
        return array_map(
            static fn (self $category): array => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
