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

ينسخ ملف البيئة فقط عند عدم وجوده؛ لا تستبدل ملف إعدادات موجوداً. صفحة Laravel الأولية تظهر على http://localhost:8000.

الإعدادات الافتراضية: اسم Methaq، اتصال MySQL بقاعدة methaq، جلسات وcache بالملفات لتشغيل الصفحة الأولية دون قاعدة بيانات. اضبط بيانات الاتصال المحلية قبل تشغيل migrations؛ لم تنشأ قاعدة البيانات ولم تشغّل migrations ضمن التثبيت. إعداد queue يستخدم database، ولا يشغل worker قبل تجهيز جداولها.

```sh
php artisan test
composer validate --strict
```

تم تأسيس Laravel فقط. React وInertia والمحرر والمصادقة الكاملة لم تثبت بعد. صفحة الترحيب مؤقتة. User في app/Domains/Users/Models حسب معمارية المشروع. لا ترفع .env أو vendor إلى Git.
