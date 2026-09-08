# تنفيذ قاعدة البيانات — 2026-09-09

تم تنفيذ migrations للجداول الـ13 المعتمدة، وModels وEnums وFactories والعلاقات. جداول Laravel التشغيلية (sessions، password_reset_tokens، cache، cache_locks، jobs، job_batches، failed_jobs) منفصلة عن هذا العدد، إضافة إلى سجل migrations.

## الجداول

users، templates، template_assets، template_asset_links، asset_collections، asset_collection_items، events، event_designs، orders، payment_webhooks، design_renders، rsvps، audit_logs.

الملفات في database/migrations. user migration الأساسي استكمل قبل أي تطبيق على قاعدة مشروع؛ لا يلزم ALTER لإصدار منشور سابق لأن التطبيق لم ينشر. Models داخل Domains، وAuditLog ضمن Users. جدول الربط يستخدم belongsToMany دون نموذج مستقل أو id اصطناعي.

## قرارات تنفيذية

- MySQL 8.0.16+ شرط صريح قبل إنشاء جدول users؛ لا تمرير migrations على MySQL 5.7 الذي لا يفرض CHECK. MariaDB وSQLite ليسا هدفاً لهذه migrations.
- InnoDB، utf8mb4، DATETIME(6) للأعمال، واتصال MySQL بتوقيت UTC.
- الحالات والمعرفات الخارجية تستخدم ASCII بمقارنة حساسة للحالة؛ uppercase غير المسموح يرفض، ومعرفان مختلفان بحالة الحرف لا يدمجان.
- FK مركب orders(event_id,user_id) إلى events(id,user_id) يمنع الطلب المنسوب لمالك آخر على مستوى قاعدة البيانات أيضاً.
- صور النسخة المنشورة وإعدادات المشهد واللوحة وrevision وrendered_at تحفظ معاً؛ CHECK يرفض إصداراً جزئياً أو revision أكبر من المسودة.
- UNIQUE لحفظ تصميم واحد لكل مناسبة، ومعرفات الدفع وإشعاراته ومهام التصيير وtoken RSVP داخل المناسبة وترتيب عناصر المجموعة.
- المبالغ BIGINT بوحدات صغرى، وcast string يحفظ دقة التمثيل عند العرض؛ الأسعار في factories بيانات اختبار وليست تسعيراً معتمداً.
- users يحتوي حقول 2FA مشفرة ومخفية بواسطة casts؛ نظام تسجيل الدخول والصلاحيات والمصادقة الثنائية الفعلي لم ينفذ بهذه المهمة.
- role/status محميان من mass assignment في User. بقية Models تحتفظ بالحماية الافتراضية، وستكتب Actions الحقول المتحقق منها صراحة.
- لا ينشئ DatabaseSeeder حساباً افتراضياً أو كلمة مرور مشتركة. factories للاختبارات والتطوير فقط.
- audit_logs بلا updated_at؛ سياسات تسجيل الإجراءات ومنع تعديلها في طبقة التطبيق تأتي في مرحلة الإدارة. لم نضف ادعاء حماية من مدير قاعدة البيانات.

## ما لا تفرضه الجداول وحدها

الصلاحية عند الزيارة، حساب عشرة أيام، الأسماء المحجوزة، ملكية القراءة، التحقق البنيوي من JSON ومراجع الأصول، السعر والعملات المدعومة، مطابقة Webhook بالبوابة، وربط صورة نهائية بدفع موثوق تحتاج Actions/Policies ومهامها لاحقاً. البيانات المرجعية داخل JSON ليست مفاتيح أجنبية؛ سيمنع منطق المكتبة حذف الأصول المشار إليها عند تنفيذه. schema لا تعني أن منطق الدفع أو النشر قد اكتمل.

## التحقق

استخدم MySQL 8.0.40 المرفق بـ MAMP بخادم مؤقت مستقل وsocket خاص في /private/tmp/methaq-schema-mysql. لم تستخدم بيانات MAMP الحالية ولم تعدّل .env ليرتبط بالخادم المؤقت.

نجح migrate ثم rollback لجميع migrations ثم migrate مجدداً. نجح 23 اختباراً / 69 assertion، تشمل قيود FK وCHECK والتكرار وmicroseconds وcasts والعلاقات وحماية تعيين الدور وتشفير حقول 2FA. Pint وgit diff --check ناجحان.

## إعادة الاختبار

أنشئ قاعدة MySQL فارغة ومخصصة للاختبار باسم methaq_schema_test، مع مستخدم اختبار يملك الصلاحية عليها فقط. مرر بيانات الاتصال المحلية ثم:

```sh
vendor/bin/phpunit --configuration phpunit.mysql.xml
```

الاختبارات تستخدم RefreshDatabase وتعيد إنشاء جداول قاعدة الاختبار؛ يمنع الاختبار اسماً لا ينتهي بـ _test. لا تستخدم بيانات اتصال قاعدة فعلية. DB_HOST/DB_PORT أو DB_SOCKET وDB_USERNAME/DB_PASSWORD تحدد من البيئة المحلية. تشغيل php artisan test بالإعداد الافتراضي SQLite يفحص الأساس فقط ويتخطى اختبارات schema بوضوح؛ لا يعد ذلك تحققاً من MySQL.

## تطبيق قاعدة التطوير — مكتمل

بتاريخ 2026-09-09 وبعد موافقة المستخدم الصريحة على استخدام إعدادات MAMP المحلية، تم التحقق من MySQL 8.0.40 ومن خلو قاعدة methaq من الجداول، ثم إنشاء قاعدة التطوير وضبط .env محلياً وتطبيق php artisan migrate.

نجحت جميع migrations الـ15 وأكد migrate:status اكتمالها. تحتوي القاعدة على 13 جدول أعمال و7 جداول تشغيلية وسجل migrations. لم يستخدم migrate:fresh أو rollback على قاعدة التطوير، ولم تمس قواعد أخرى. بيانات الاتصال لا تدخل Git، و.env بصلاحية 0600. لا توجد حسابات أو مناسبات تجريبية أضيفت لهذه القاعدة.
