# مخطط قاعدة البيانات وقاموس البيانات

> تحديث 2.0: راجع [المخطط الشامل الجديد](09-methaq-master-plan-v2.md) قبل التنفيذ. يتقدم على هذا الملف عند التعارض، خصوصاً مكتبة العناصر المشتركة والمشهد التفاعلي والجداول ودورة نشر النسخة المطلوبة.

## قواعد مشتركة

MySQL 8.0.16+ مقترح لتفعيل CHECK فعلياً، وInnoDB وutf8mb4. كل جدول له id من BIGINT UNSIGNED PK وcreated_at/updated_at من DATETIME(6) بتوقيت UTC، إلا جدول الربط الذي يستخدم PK مركباً. الحقول مطلوبة ما لم تذكر NULL. حالات VARCHAR تمثل PHP backed enums وتقيّد بـ CHECK عند التنفيذ. لا أموال float.

الحذف: لا حذف صلب عبر الواجهة في الإصدار الأول. تعطيل القالب/الأصل بواسطة is_active. مفاتيح الجداول التجارية RESTRICT للمحافظة على السجلات، وحذف روابط القوالب فقط CASCADE. سياسة حذف البيانات الشخصية قبل الإطلاق في سجل القرارات.

## 1. users — Users

| الحقل | النوع/القيد |
|---|---|
| name | VARCHAR(120) |
| email | VARCHAR(254)، UNIQUE؛ يطبّع قبل التخزين |
| password | VARCHAR(255)، hash فقط |
| phone | VARCHAR(32) NULL، ليس رقماً حسابياً |
| email_verified_at | DATETIME(6) NULL |
| remember_token | VARCHAR(100) NULL |

العلاقات: hasMany events، orders. تهيئة auth provider لمسار Domains/Users/Models/User.

## 2. templates — Editor

| الحقل | النوع/القيد |
|---|---|
| name | VARCHAR(150) |
| category | VARCHAR(20): wedding أو graduation |
| thumbnail_path | VARCHAR(1024)، مفتاح تخزين |
| default_design_json | JSON، مخطط Canvas مقبول |
| schema_version | SMALLINT UNSIGNED، افتراضي 1 |
| is_active | BOOLEAN، افتراضي true |

فهرس (category,is_active). hasMany events وbelongsToMany template_assets. يحفظ التصميم الابتدائي نسخة للمناسبة؛ تعديل القالب لا يغير مناسبات سابقة.

## 3. template_assets — Editor

| الحقل | النوع/القيد |
|---|---|
| name | VARCHAR(150) |
| type | VARCHAR(20): background/frame/icon/font |
| original_path | VARCHAR(1024)، تخزين خاص |
| preview_path | VARCHAR(1024)، نسخة مناسبة للمعاينة |
| mime_type | VARCHAR(100)، قيمة يتحقق منها الخادم |
| width / height | INT UNSIGNED NULL؛ مطلوبان للصور |
| metadata | JSON NULL؛ فقط مفاتيح مسموحة مثل font_family |
| is_active | BOOLEAN، افتراضي true |

فهرس (type,is_active). الأصول المشار إليها في تصاميم محفوظة لا تحذف. الرخص وإتاحة الخطوط للمتصفح تراجع قبل إدراجها.

## 4. template_asset_links — Editor، إضافة مقترحة

| الحقل | النوع/القيد |
|---|---|
| template_id | BIGINT UNSIGNED FK templates |
| template_asset_id | BIGINT UNSIGNED FK template_assets |
| sort_order | INT UNSIGNED، افتراضي 0 |

PK(template_id,template_asset_id)، فهرس(template_asset_id). علاقة many-to-many تسمح بإعادة استخدام الأصل. Mix & Match في الإصدار الأول من الأصول المرتبطة بالقالب المختار.

## 5. events — Events

| الحقل | النوع/القيد |
|---|---|
| user_id | BIGINT UNSIGNED FK users |
| template_id | BIGINT UNSIGNED FK templates |
| title | VARCHAR(200) |
| subdomain | VARCHAR(63) ASCII lowercase، UNIQUE |
| event_date | DATETIME(6) UTC |
| timezone | VARCHAR(64)، IANA؛ قيمة الإدخال الافتراضية Africa/Tripoli |
| expires_at | DATETIME(6) UTC، يحسب على الخادم |
| status | VARCHAR(20): draft/published/expired، افتراضي draft |
| is_paid | BOOLEAN، افتراضي false، الخادم فقط يكتبه |
| paid_at | DATETIME(6) NULL |
| published_at | DATETIME(6) NULL |

الفهارس (user_id,created_at)، (status,expires_at). CHECK: expires_at > event_date، وpublished يستلزم is_paid=true. أي حالة مدفوعة تستلزم paid_at. is_paid قيمة مخزنة مشتقة من طلب completed ولا تقبل mass assignment من المستخدم.

العلاقات belongsTo user/template؛ hasOne event_design؛ hasMany orders/rsvps. النطاق لا يعاد استخدامه بعد الانتهاء في الإصدار الأول منعاً لوصول روابط قديمة إلى مناسبة أخرى.

## 6. event_designs — Editor

| الحقل | النوع/القيد |
|---|---|
| event_id | BIGINT UNSIGNED FK events، UNIQUE |
| design_json | JSON؛ آخر مسودة سليمة |
| schema_version | SMALLINT UNSIGNED، افتراضي 1 |
| revision | INT UNSIGNED، افتراضي 1، CHECK > 0 |
| watermarked_preview_path | VARCHAR(1024) NULL |
| preview_revision | INT UNSIGNED NULL |
| final_render_path | VARCHAR(1024) NULL |
| published_revision | INT UNSIGNED NULL |
| rendered_at | DATETIME(6) NULL |

الحقول watermarked_preview_url وfinal_render_url المطلوبة بالـ PRD تقدم في DTO كروابط مؤقتة/مسارات تطبيق محمية؛ نخزن path بدلاً من رابط دائم أو رابط موقّع ينتهي. final_render_path لا يضبط قبل الدفع ونجاح التصدير. preview_revision يحدد هل المعاينة توافق المسودة. النسخة المنشورة السابقة تبقى حتى ينجح البديل.

## 7. orders — Payments

| الحقل | النوع/القيد |
|---|---|
| user_id | BIGINT UNSIGNED FK users |
| event_id | BIGINT UNSIGNED FK events |
| amount | BIGINT UNSIGNED، بوحدات العملة الصغرى؛ CHECK > 0 |
| currency | CHAR(3) ASCII uppercase، عملة مدعومة لدى البوابة |
| status | VARCHAR(20): pending/completed/failed |
| gateway | VARCHAR(20): stripe/tap |
| transaction_id | VARCHAR(191) ASCII NULL |
| checkout_id | VARCHAR(191) ASCII NULL |
| idempotency_key | CHAR(36) ASCII UNIQUE |
| completed_at | DATETIME(6) NULL |
| failure_code | VARCHAR(100) NULL؛ بلا بيانات حساسة |

UNIQUE(gateway,transaction_id)، UNIQUE(gateway,checkout_id)، فهرس(event_id,status)، (user_id,created_at). يضمن Action أن orders.user_id يطابق مالك event؛ يختبر هذا invariant. السعر يأتي من إعدادات خادم مثبتة في الطلب، والتحويل إلى الوحدة الصغرى بحسب العملة وليس افتراض منزلتين للجميع. محاولة جديدة تنشئ طلباً جديداً؛ لا تمسح سجل الفشل.

## 8. payment_webhooks — Payments، إضافة مقترحة

| الحقل | النوع/القيد |
|---|---|
| gateway | VARCHAR(20) |
| gateway_event_id | VARCHAR(191) ASCII |
| order_id | BIGINT UNSIGNED FK orders NULL حتى المطابقة |
| event_type | VARCHAR(100) |
| payload | JSON، بيانات لازمة فقط ومحجوبة من العرض العام |
| status | VARCHAR(20): received/processing/processed/failed |
| attempts | INT UNSIGNED، افتراضي 0 |
| received_at | DATETIME(6) |
| locked_until / processed_at | DATETIME(6) NULL |
| error_code | VARCHAR(100) NULL |

UNIQUE(gateway,gateway_event_id)، فهرس(status,locked_until). لا تخزين لبيانات بطاقات أو secrets. لا يسجل إشعار غير موثوق كإشعار قابل للمعالجة. السجل الدائم يعاد التقاطه إن فشل dispatch. processed يعني انتهت المعالجة لا بالضرورة دفع ناجح.

## 9. design_renders — Editor، إضافة مقترحة

| الحقل | النوع/القيد |
|---|---|
| event_design_id | BIGINT UNSIGNED FK event_designs |
| revision | INT UNSIGNED |
| kind | VARCHAR(20): preview/final |
| design_snapshot | JSON، نسخة ثابتة للمهمة |
| status | VARCHAR(20): pending/processing/completed/failed |
| attempts | INT UNSIGNED، افتراضي 0 |
| available_at | DATETIME(6) |
| locked_until | DATETIME(6) NULL |
| output_path | VARCHAR(1024) NULL |
| completed_at | DATETIME(6) NULL |
| error_code | VARCHAR(100) NULL |

UNIQUE(event_design_id,revision,kind)، فهرس(status,available_at)، (status,locked_until). ينشأ داخل transaction نفسها التي تتطلب التصيير. Worker مع lease وإعادة محاولة يضمن التقاط المهام بعد التعطل. النتيجة القديمة لا تستبدل revision أحدث. لا يتضمن هذا الجدول تاريخ تصميم قابل للاسترجاع للمستخدم.

## 10. rsvps — RSVP

| الحقل | النوع/القيد |
|---|---|
| event_id | BIGINT UNSIGNED FK events |
| submission_token | CHAR(36) ASCII؛ يولده العميل للمحاولة ويحفظه عند إعادة الإرسال |
| guest_name | VARCHAR(120) |
| guest_phone | VARCHAR(32) NULL |
| attending_status | VARCHAR(20): attending/declined/maybe |
| companions_count | SMALLINT UNSIGNED، افتراضي 0 |
| notes | TEXT NULL؛ حد الإدخال 1000 حرف |

UNIQUE(event_id,submission_token)، فهرس(event_id,attending_status). declined يستلزم companions_count=0. الحد المقترح للمرافقين 10 قابل للتهيئة. الاسم والهاتف لا يمثلان هوية موثقة ولا يجعلان RSVP فريداً؛ token يمنع تكرار نفس الإرسال فقط. إجمالي الحضور المؤكد = مجموع (1 + companions_count) للسجلات attending.

## ملاحظة عدد الجداول

المتطلبات الأصلية: users، templates، template_assets، events، event_designs، orders، rsvps = **7 جداول**. مع الإضافات الثلاثة يصبح الإجمالي **10 جداول أعمال**. جداول Laravel التشغيلية مثل sessions وpassword_reset_tokens وjobs وfailed_jobs وcache تحدد بحسب drivers ولا تدخل في هذا العدد.

## ترتيب migrations

users → templates → template_assets → template_asset_links → events → event_designs → orders → payment_webhooks → design_renders → rsvps. اختبارات الفهارس وCHECK على MySQL الفعلي، لا الاكتفاء بـ SQLite.
