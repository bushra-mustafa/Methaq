# Methaq

منصة ميثاق لإنشاء وتخصيص الدعوات الرقمية.

## المتطلبات

- PHP 8.4 لتشغيل الاعتماديات المقفلة حالياً؛ استهداف PHP 8.3 يحتاج إعادة حل الاعتماديات وفحصها لذلك الإصدار.
- Composer 2، وMySQL قبل تشغيل migrations.
- [الخطة المعتمدة](requirements/09-methaq-master-plan-v2.md).
- [قائمة التنفيذ](requirements/10-execution-checklist.md).

## التشغيل المحلي

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

ينسخ ملف البيئة فقط عند عدم وجوده؛ لا تستبدل ملف إعدادات موجوداً. صفحة ميثاق تظهر على http://localhost:8000.

الإعدادات الافتراضية: اسم Methaq، اتصال MySQL بقاعدة methaq، جلسات وcache بالملفات لتشغيل الصفحة الأولية دون قاعدة بيانات. اضبط بيانات الاتصال المحلية قبل تشغيل migrations؛ تم ربط قاعدة التطوير methaq على MAMP وتطبيق migrations لاحقاً بتاريخ 2026-09-09. إعداد queue يستخدم database، ولا يشغل worker قبل تجهيز جداولها.

```sh
php artisan test
composer validate --strict
```

تم تأسيس Laravel وتنفيذ migrations وModels وEnums وFactories للجداول الـ13. React 19 وInertia v2 وTypeScript وFabric v6 مثبتة؛ المحرر والمصادقة الكاملة لم ينفذا بعد. صفحة ميثاق الحالية مؤقتة. User في app/Domains/Users/Models حسب معمارية المشروع. لا ترفع .env أو vendor إلى Git.

تفاصيل الجداول وتشغيل اختبار MySQL في [سجل قاعدة البيانات](requirements/12-database-implementation.md). نجحت الاختبارات أولاً على قاعدة معزولة، ثم طُبقت migrations على قاعدة التطوير الرئيسية methaq.

## بناء الواجهة

```sh
npm ci
npm run typecheck
npm run build
php artisan serve
```

للتطوير شغّل npm run dev مع خادم Laravel. Node الحالي 23.6؛ يوصى ببيئة Node LTS متوافقة مع Vite (22.12+). الخطوط محلية في public/brand/fonts؛ تحذير Vite عن روابط الخطوط المطلقة يعني أنها تقدم وقت التشغيل وقد تحقق ظهورها في المتصفح. ملفات build وnode_modules لا تدخل Git.
