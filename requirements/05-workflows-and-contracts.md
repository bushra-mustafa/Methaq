# سير العمل والعقود

## 1. إنشاء المناسبة

CreateEventRequest يتحقق من title، template_id فعال، event_date مستقبلي وtimezone صحيح. CreateEventDTO لا يقبل is_paid أو status أو expires_at. CreateEventAction ينشئ مناسبة draft وتصميماً من نسخة القالب داخل transaction واحدة. SubdomainGenerator يولد label ASCII من العنوان مع suffix عشوائي، أو event-suffix للعناوين التي لا تنتج slug؛ regex بطول 1–63 بلا شرطة أول/آخر الاسم، ورفض المحجوزات. UNIQUE قاعدة البيانات مع retry محدود يعالج السباق. لا يقبل event_id أو user_id من العميل لتحديد المالك.

ExpirationCalculator يحول موعد المناسبة إلى منطقتها، يضيف 10 أيام تقويمية في الوقت المحلي نفسه، ثم يخزن UTC. مقارنة الوقت: now >= expires_at يعني منتهي. تعديل موعد المسودة يعيد الحساب. تجميد التاريخ والنطاق بعد الدفع مقترح للإصدار الأول حتى تحدد سياسة التعديل.

## 2. حفظ التصميم

SaveDesignRequest: design_json + expected_revision. SaveDesignDTO يحوي مخططاً معروفاً. SaveDesignAction يتحقق من الملكية والصلاحية والأصول المسموحة، ثم ينفذ تحديثاً ذرياً مشروطاً بمطابقة revision ويزيده. عند التعارض يعيد خطأ مفهوم لاستعادة أحدث نسخة؛ لا يمسح تعديلات المستخدم المحلية تلقائياً. ينشئ مهمة preview للنسخة الجديدة في transaction نفسها.

مقترحات حدود أولية: JSON حتى 1 MiB، 200 طبقة، عمق محدود 12، نص الطبقة حتى 2000 حرف، أرقام محدودة ومنتهية، عرض/ارتفاع منطقي حتى 4096. تثبت بعد قياس قوالب فعلية. منع script وHTML وروابط خارجية وdata URLs ومراجع الملفات؛ الصور والخطوط تشير إلى asset_id مصرح به. لا تمرير Fabric JSON الخام إلى المتصفح/المصدر دون normalization وقائمة خصائص مسموحة.

useCanvas ينشئ Canvas ويتخلص منه عند unmount، وينظف listeners ويتعامل مع تحميل الأصول غير المتزامن. يخزن إحداثيات التصميم المنطقية مستقلة عن حجم العرض، ويطبق حد backing resolution للمعاينة غير المدفوعة وعلامة مائية خارج طبقات المستخدم. ApplyWatermarkAction/WatermarkProcessor يفرضان علامة مائية على صورة الخادم كذلك. لا يعد is_paid القادم من React صلاحية خادم.

## 3. الدفع والنشر

CreateCheckoutAction يقفل المناسبة، يرفض المنتهية والمدفوعة، يعيد محاولة pending الصالحة حيث أمكن، ويثبت amount/currency من إعداد خادم. الاتصال بالبوابة خارج transaction طويلة وبـ idempotency_key للطلب؛ يعيد المحاولة بالمفتاح نفسه عند انقطاع الرد.

ReceivePaymentWebhookAction يفوض التحقق من توقيع raw body إلى Gateway، يتحقق من البيئة وحساب التاجر، ويحفظ event_id فريداً ثم يرسل acknowledgment بعد الحفظ الدائم. Worker/عملية استرداد تلتقط غير المعالج. ProcessPaymentAction يطابق الطلب ومعرف الدفع والمبلغ والعملة والحالة النهائية من مصدر موثوق، ويقفل الطلب والمناسبة في transaction بترتيب ثابت.

عند نجاح الدفع: order=completed، completed_at، event.is_paid=true، paid_at. PublishEventAction يضبط published وpublished_at إذا لم تنتهِ المناسبة. يسجل design_renders(final) لنفس revision داخل transaction، ويطلق EventPublished بعد commit لتسريع الإرسال. مهمة دورية تستعيد المهام التي لم تصل للطابور. إشعار مكرر يعيد نجاحاً بلا أثر إضافي؛ completed لا يعود failed بسبب إشعار متأخر. دفع ناجح متأخر لمناسبة انتهت يوثق مالياً ولا يعيد فتح الرابط؛ يحال للمراجعة وفق سياسة الاسترجاع.

رجوع المتصفح من صفحة الدفع لا يفعّل المناسبة. صفحة الدفع تظهر «بانتظار تأكيد الدفع» حتى يثبت الخادم. إذا وصلت دفعتان ناجحتان لطلبين مختلفين تحفظان كما حدثتا، ولا يحدث نشر مضاعف؛ يطلق تنبيه تسوية مالية ولا يطبق استرجاع آلي بلا سياسة.

## 4. التصيير والنشر المتجدد

CanvasEngineService يستدعي CanvasExporter موثوقاً باستخدام snapshot وأصول خاصة. preview دائماً منخفض الدقة وبعلامة؛ final يتطلب is_paid والتحقق من الصلاحية مجدداً. الـ exporter يمنع network URLs غير المصرح بها لتفادي SSRF، ويحدد وقت التنفيذ والذاكرة والحجم.

على النجاح يخزن الناتج خاصاً ثم يحدث المؤشر في transaction. نتيجة final لا تصبح منشورة إذا انتهت المناسبة أو تغير revision أثناء المهمة؛ تسجل النتيجة وتطلب نسخة حديثة دون استبدال نسخة أحدث. القالب المدفوع الجديد يعرض Preparing إلى أن تجهز أول نسخة؛ لا يعرض نسخة عالية الدقة جزئية. عند تعديل مناسبة منشورة، تبقى الصورة السابقة ظاهرة ويستخدم المالك UpdatePublishedDesignAction صريحاً لطلب نشر التعديل؛ الحفظ التلقائي لا يغير ما يراه الضيوف.

حالة العرض مشتقة: expired أولاً، ثم draft، ثم published + لا final = preparing، ثم published + final = ready. فشل التصيير لا يلغي الدفع؛ يعرض للمالك إعادة المحاولة وتنبيهاً تشغيلياً.

## 5. الضيوف والانتهاء

ResolveEventSubdomain يتحقق من المضيف ويحمّل المناسبة. CheckEventExpiration يمنع show وRSVP وتسليم الصور إذا now >= expires_at ولو لم يعمل Cron؛ draft غير متاح للعامة. انتهاء معروف يعرض 410، المجهول/المسودة 404. تسليم الصورة عبر مسار محمي يتحقق من host والمناسبة والدفع والانتهاء؛ منع cache عام يتجاوز هذه الشروط. أي signed URL مباشر يجب ألا يمتد بعد expires_at، والأفضل proxy إذا كان التعطيل الفوري مطلوباً.

SubmitRsvpAction يتحقق مجدداً داخل عملية الحفظ من صلاحية المناسبة، ويستخرج event_id من السياق. validation وCSRF وrate limiting مقترح 10 طلبات/دقيقة لكل مناسبة ومصدر، مع مراعاة الشبكات المشتركة. رد مكرر بنفس token يعيد النتيجة السابقة، وpayload مختلف بنفس token يرفض. الاسم والملاحظات نصوص escaped وليست HTML. إجابات الضيوف يعرضها المالك فقط.

ExpireEventsCommand مجدول يومياً في routes/console.php: تحديث draft وpublished حيث expires_at <= now إلى expired. الأمر idempotent وبدفعات. scheduler runner يعمل كل دقيقة في الخادم، لكن الأمر المطلوب يومي. لا حذف بيانات عند الانتهاء.

## عقود الواجهة المقترحة

| العقد | حقوله |
|---|---|
| EventDTO | id، title، subdomain، event_date ISO8601، timezone، expires_at، status union، is_paid، presentation_state |
| CanvasData | schemaVersion، width، height، background، layers: Layer[] |
| Layer | discriminated union عبر type: text/image/shape؛ id، position، scale، rotation، opacity، locked، visible |
| TextLayer | text، fontAssetId، fontSize، fill، alignment |
| ImageLayer | assetId؛ لا original URL مرسل من العميل |
| ShapeLayer | shapeKind محدود، fill، stroke، dimensions |
| SaveDesignDTO | CanvasData، expectedRevision |
| PaymentDTO | orderId، status، amountMinor كـ string، currency؛ لا أسرار بوابة |

معرفات BIGINT تسلسل إلى string في TypeScript لتجنب فقدان الدقة. Fabric serialization تفصيل adapter؛ نسخة schema للتطبيق مستقلة عن نسخة Fabric. Runtime validation ضروري لأن TypeScript لا يتحقق من JSON الوارد.

## المسارات المقترحة

| المضيف | الطريقة والمسار | الحماية/النتيجة |
|---|---|---|
| الرئيسي | GET / | Landing Inertia |
| الرئيسي | GET /app | auth، Dashboard |
| الرئيسي | POST /app/events | auth + CSRF، CreateEventAction ثم redirect |
| الرئيسي | GET /app/events/{event}/editor | auth + policy، Editor |
| الرئيسي | PUT /app/events/{event}/design | auth + policy + CSRF، save ثم redirect مع revision/errors |
| الرئيسي | POST /app/events/{event}/checkout | auth + policy + CSRF، Checkout |
| الرئيسي | POST /app/events/{event}/publish-update | auth + policy + CSRF، طلب التصيير |
| الرئيسي | GET /app/events/{event}/guests | auth + policy، Guests |
| الرئيسي | POST /webhooks/{gateway} | توقيع البوابة؛ استثناء CSRF لهذا المسار فقط |
| نطاق الضيوف | GET / | domain + expiration، Show/Preparing |
| نطاق الضيوف | GET /render | domain + paid + expiration، الصورة |
| نطاق الضيوف | POST /rsvp | domain + expiration + CSRF + throttle |

## مراجع تنفيذية رسمية

- [Laravel 12 Scheduling](https://laravel.com/docs/12.x/scheduling): جدولة الأمر وrunner.
- [Laravel 12 Queues](https://laravel.com/docs/12.x/queues): إرسال المهام بعد تثبيت transaction.
- [Stripe Webhooks](https://docs.stripe.com/webhooks): التحقق من التوقيع والتعامل مع الإشعارات المكررة. تفاصيل Tap تفحص من وثائقه عند اختيار البوابة؛ لا يفترض أنه يطابق Stripe.
