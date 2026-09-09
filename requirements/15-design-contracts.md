# ميثاق — عقد التصميم الإنتاجي

الحالة: منفذ ومتحقق منه بتاريخ 2026-09-09 ضمن المرحلة 02. هذا العقد يحل محل `PrototypeDesign` التجريبي عند بناء المحرر والحفظ الخادمي. لا يربط مسار حفظ أو يفعّل النشر أو الدفع بمفرده.

## الوثيقة الواحدة

`DesignDocument` بإصدار 1 يجمع `canvas` و`palette` و`scene`. يحفظ كل قسم في عموده الحالي داخل `event_designs`، وتبقى قيمة `revision` الواحدة في الصف هي مرجع اتساق الأقسام الثلاثة.

| القسم | الغرض |
|---|---|
| `schemaVersion` | إصدار عقد ميثاق المستقل عن إصدار Fabric |
| `canvas` | المقاس والخلفية والطبقات المرتبة |
| `palette` | ستة أدوار لونية ثابتة |
| `scene` | الدخول والظرف والمؤثرات والصوت وسياسة الحركة |

ترتيب `canvas.layers` هو ترتيب الرسم. لا يوجد حقل ترتيب ثانٍ. كل طبقة تحمل معرفاً ثابتاً وفريداً، إطاراً منطقياً، الدوران، الشفافية، الإظهار والقفل، ثم خصائص مميزة حسب `type`:

- `text`: المحتوى واللغة `ar/en/mixed` والاتجاه `rtl/ltr/auto` والمحاذاة ومرجع الخط وإصداره وحجمه ووزنه ولونه.
- `image`: `assetId` داخلي وإصداره وطريقة `contain/cover`، دون URL أو data URL.
- `shape`: `rectangle/ellipse/line` مع تعبئة وحد ولون وسماكة محدودة.

القيمة اللونية union صريح: إما دور من اللوحة أو قيمة `#RRGGBB`. الأدوار هي `background` و`surface` و`primaryText` و`secondaryText` و`accent` و`effect`.

## المقاس والحدود

| الحد | القيمة في الإصدار 1 |
|---|---:|
| مقاس البطاقة المنطقي | 1080×1920 |
| حجم JSON الأقصى | 1 MiB |
| عدد الطبقات الأقصى | 200 |
| طول نص الطبقة | 2000 حرف |
| عمق البيانات | 12 |
| البعد المنطقي العام | 4096 |
| عدد المؤثرات | 2، دون تكرار النوع |
| إصدار الأصل | 1–65535 |

كل رقم يجب أن يكون finite. معرف الأصل BIGINT يرسل كنص رقمي موجب لحماية الدقة في JavaScript. المعرفات الخارجية وHTML والكود والحقول غير المعروفة لا تدخل الناتج المنظم.

## سجل المشهد

السجل الموثوق الأول يحتوي ظرف `classic-fold@1` بحد 12 حرفاً للختم، ومؤثرين:

| المؤثر | الإصدار | الشدة | السرعة |
|---|---:|---:|---:|
| `sparkle` | 1 | 0–1 | 0.25–2 |
| `smoke` | 1 | 0–0.8 | 0.25–1.5 |

الدخول `direct` مدته صفر، و`fade` أو `envelope` مدتهما 100–5000ms. تفعيل الصوت يتطلب `assetId` داخلياً؛ مستوى الصوت 0–1. سياسة الحركة `system/reduced/off`.

نسخة الـpreset والمؤثر جزء من الوثيقة حتى يمكن تفسير snapshots القديمة. أي إصدار جديد يضاف إلى السجل، ولا يغير معنى إصدار سابق.

## Fabric والتخزين

`FabricAdapter` يحول طبقات ميثاق إلى descriptors معروفة لتهيئة Fabric ويعيد التحويل مع حفظ الدلالات، وخصوصاً ارتباط اللون باللوحة ومرجع الأصل. لا نخزن Fabric JSON الخام ولا مكونات React. تحميل الصورة والخط يتم لاحقاً من `assetId` بعد تحقق الخادم.

`DesignDocumentData::toStorageColumns()` يفصل الوثيقة إلى `design_json` و`palette_json` و`scene_json` و`schema_version`. عملية الحفظ القادمة تستخدم `SaveDesignData(document, expectedRevision)` وتحدث الأقسام الثلاثة وتزيد revision ذرياً. `DesignSnapshotData` يثبت الوثيقة ورقم revision المطلوب للتصيير أو النشر.

## الحالات والانتقالات

| النطاق | الانتقالات المسموحة |
|---|---|
| المناسبة | `draft → published/expired`، و`published → expired`؛ `expired` نهائية |
| الطلب | `pending → completed/failed`؛ الحالتان نهائيتان، والمحاولة الجديدة طلب جديد |
| التصيير | `pending → processing → completed/failed`؛ استرداد lease أو retry يعيد `processing/failed → pending` |
| webhook | `received → processing → processed/failed`؛ الاسترداد يعيد `processing/failed → received` |

حالة العرض مشتقة وليست عموداً جديداً: `expired` أولاً، ثم `draft`، ثم `preparing` عند نشر بلا صورة نهائية، ثم `ready`.

## ملفات التنفيذ والتحقق

- TypeScript في `resources/js/Types` مع parser/serializer يقرأ `unknown` ويعيد نسخة allowlisted فقط.
- PHP في `app/Domains/Editor/DTOs` مع enums وعقود immutable وحدود متطابقة.
- سجل القدرات في TypeScript و`DesignSchema.php` بالقيم نفسها.
- اختبارات round-trip عبر JSON وFabric، ورفض URL/الإصدارات والحدود والتكرار، واختبارات DTOs وانتقالات الحالات.

التحقق الكامل من الأصول الفعالة وملكية المناسبة وعمق Form Request وتعارض revision ينفذ مع `SaveDesignRequest` و`SaveDesignAction` في المرحلة 08؛ لا يوجد endpoint يقبل هذا العقد بعد.
