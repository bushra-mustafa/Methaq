# Methaq

منصة ميثاق لإنشاء وتخصيص الدعوات الرقمية.

## المتطلبات

- PHP `>=8.4.1` و`<9.0` للاعتماديات المقفلة حالياً: يحدد `composer.json` و`composer.lock` قيد المشروع `^8.3`، لكن الحزم المقفلة من Symfony 8.1 تتطلب `>=8.4.1`. اجتاز PHP 8.4.1 المثبت محلياً `composer check-platform-reqs`؛ كما يؤكد `composer prohibits php 8.4.0 --locked` أن 8.4.0 لا يفي بمتطلبات الحزم المقفلة. عند تحديث الاعتماديات، أعد التحقق من الحد الأدنى.
- Composer 2. يتطلب تشغيل migrations واختبارات MySQL اتصالاً بقاعدة MySQL.
- React 19 وInertia.js v2 وTypeScript وFabric.js v6؛ الإصدارات المقفلة موضحة في `package.json` و`package-lock.json`.
- [الخطة المعتمدة](requirements/09-methaq-master-plan-v2.md) و[قائمة التنفيذ](requirements/10-execution-checklist.md).

## إعداد تثبيت جديد

```sh
composer install
if [ ! -f .env ]; then cp .env.example .env; fi
if grep -q '^APP_KEY=$' .env; then php artisan key:generate; fi
php artisan migrate
php artisan serve
```

يضبط الأمر الشرطي ملف `.env` فقط إن لم يكن موجوداً، ويولّد `APP_KEY` فقط عندما يكون فارغاً. راجع إعدادات `.env` واضبط اتصال MySQL قبل `php artisan migrate`. تستخدم `.env.example` قاعدة MySQL باسم `methaq` والجلسات وcache وqueue عبر قاعدة البيانات؛ تحتاج قاعدة جديدة إلى migrations المناسبة لهذه الجداول. صفحة ميثاق متاحة افتراضياً على `http://localhost:8000`.

## تشغيل تثبيت موجود

احتفظ بملف `.env` و`APP_KEY` الحاليين. لا تنسخ `.env.example` فوق إعداد موجود ولا تشغّل `php artisan key:generate` على تثبيت ذي مفتاح مضبوط؛ تغيير المفتاح قد يبطل البيانات المشفرة والجلسات والكوكيز. بعد تحديث المصدر، استخدم `composer install` و`npm ci` عند الحاجة، ثم شغّل Laravel وVite في نافذتي طرفية منفصلتين:

```sh
php artisan serve
npm run dev
```

قبل تطبيق تغييرات قاعدة البيانات على تثبيت موجود، راجع حالة migrations:

```sh
php artisan migrate:status
```

شغّل `php artisan migrate` فقط عندما تتطلب التغييرات المراجعة ذلك.

```sh
php artisan test
composer validate --strict
```

## البنية الحالية

يعتمد التطبيق Modular DDD مع حدود المجالات التالية في `app/Domains` و`resources/js/Domains`:

- `Events`: إنشاء المناسبات وبياناتها وملكيتها.
- `Editor`: مكتبة القوالب والأصول، عقد التصميم، محرر البطاقة ومشهد الدعوة.
- `Payments`: توجد نماذج `Order` و`PaymentWebhook` وEnums؛ تكامل بوابة الدفع ومعالجة الدفع الفعلية غير منفذين.
- `RSVP`: استقبال ردود الضيوف.
- `Users`: الحسابات والصلاحيات وسجل التدقيق.

المجالات غير متساوية في اكتمالها؛ أسماء المجلدات تصف حدود المسؤولية ولا تعني أن كل الميزات المخطط لها مكتملة. تنظم `app/Http/Controllers` محولات HTTP حسب `Web` و`App` و`Admin` و`Guest`، وتحتوي `resources/js/Pages` صفحات Inertia، بينما تضم `resources/js/Types` عقود TypeScript المشتركة.

ضمن `Editor`، تتبع ملفات PHP مجلدات متخصصة مثل `Actions` و`DTOs` و`Enums` و`Models` و`Policies` و`Services`. وفي الواجهة، تفصل `Components` و`Hooks` عن خدمات المجال. **يبقى `resources/js/Domains/Editor/Services` بهيكله الحالي؛ لم تُنقل ملفاته أو تُعد تسميتها.**

## المسارات والوظائف الموجودة

يجمع `routes/web.php` وحدات المسارات، مع تسجيل مسارات المصادقة من Fortify:

- `routes/public.php`: `GET /` (`Web/Home`)، و`GET /templates` (`Web/Templates`)، و`GET /about` (`Web/About`)، و`GET /contact` (`Web/Contact`).
- `routes/app.php`: مجموعة `/app` المحمية بـ`auth` و`active`، وتشمل لوحة المستخدم، إنشاء المناسبة، عرضها، محرر التصميم، العرض التقديمي، الملف الشخصي، ومسودة اقتراح Ivory.
- `PATCH /app/events/{event}/design` لحفظ وثيقة التصميم؛ يستخدم مسار حفظ مع مراجعة للتصميم.
- `routes/admin.php`: مجموعة الإدارة تحت `/app/admin` داخل مجموعة التطبيق، وتُحمى أيضاً بـ`admin.2fa` التي تتحقق من دور المدير وتفعيل المصادقة الثنائية.
- `routes/guest.php`: على نطاقات الدعوات الفرعية، يسمح `ResolvePublishedEvent` بتحميل دعوة منشورة غير منتهية؛ ويضم المسار عرض الدعوة وإرسال RSVP.
- `GET /dev/design-lab` متاح فقط في بيئتي `local` و`testing` عبر `LocalDevelopmentOnly`.

تتضمن الشيفرة واجهات Inertia للمصادقة والحسابات، وإدارة المناسبات، ومحرر البطاقة والمشهد، ومكتبة الإدارة، ودعوة الضيف وRSVP. وجود نماذج الدفع أو مسارات الدفع في الخطط لا يعني وجود معالجة دفع جاهزة: لا توجد حالياً مسارات checkout أو webhook في `routes/web.php` ولا تنفيذ مزود دفع في `app/Domains/Payments`.

تفاصيل المخطط وتشغيل اختبارات MySQL موثقة في [سجل قاعدة البيانات](requirements/12-database-implementation.md).

## بناء الواجهة

```sh
npm ci
npm run typecheck
npm run test:frontend
npm run build
```

للتطوير شغّل `npm run dev` مع خادم Laravel. الخطوط المحلية في `public/brand/fonts`، ومواد البطاقات المعتمدة للعرض في المتصفح ضمن `public/`. راجع سياسة الأصول في `AGENTS.md` قبل إضافة أصول أو تغيير مواقعها. لا ترفع `.env` أو `vendor` أو `node_modules` أو ملفات البناء إلى Git.

## الموقع العام ونظام التصميم

واجهات الموقع العام موجودة في `resources/js/Pages/Web`: `Home.tsx` و`About.tsx` و`Contact.tsx` و`Templates.tsx`. اكتمل `PublicLayout` كغلاف RTL قابل للوصول، ويجمع `Navbar` المتجاوب و`main` و`Footer` الموحد. يستخدمه `About.tsx` حالياً؛ نقل `Home` و`Contact` و`Templates` إليه ما زال قيد التنفيذ.

يعتمد المشروع Tailwind CSS v4 عبر `@tailwindcss/vite`. يربط `app.css` ملف `public-theme.css` بنظام تصميم الموقع العام فقط: الأخضر الأساسي `#074b36`، الذهبي `#b69a50`، العاجي `#f5f2e3`، وتدرجات النص والحدود، مع Manrope محلياً للنص وAmiri للعناوين، وأنصاف أقطار وظل وحالات تركيز موحّدة. لا يغيّر هذا النظام أنماط المحرر أو لوحة التحكم أو صفحة الضيف.

## توصيات مستقبلية اختيارية

هذه أفكار تنظيمية وليست تغييرات منفذة أو متطلبات حالية. إذا زادت مسؤوليات خدمات المحرر، يمكن في عمل مستقبلي مستقل دراسة تجميع اتصال HTTP ومحولات Fabric/العرض في وحدات فرعية مع المحافظة على حدود `Editor` والعقود الحالية. لا يتطلب ذلك تغيير بنية `Editor/Services` الحالية، وأي نقل أو إعادة تسمية يحتاج مراجعة مستقلة للاستخدامات والاختبارات وموافقة منفصلة.
