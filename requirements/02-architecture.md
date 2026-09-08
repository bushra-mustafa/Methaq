# المعمارية الثابتة

> تحديث 2.0: راجع [المخطط الشامل الجديد](09-methaq-master-plan-v2.md) قبل التنفيذ. يتقدم على هذا الملف عند التعارض، خصوصاً مكتبة العناصر المشتركة والمشهد التفاعلي والجداول ودورة نشر النسخة المطلوبة.

## الهيكل

```text
app/
  Domains/
    Users/Models/User.php
    Events/{Actions,Models,DTOs,Enums,Policies,Services,Events}/
    Editor/{Actions,Models,DTOs,Enums,Services,Contracts,Jobs}/
    Payments/{Actions,Models,DTOs,Enums,Gateways,Events}/
    RSVP/{Actions,Models,DTOs,Enums}/
  Http/
    Controllers/
      Web/
      App/
      Subdomains/
      Webhooks/
    Requests/{Events,Editor,Payments,RSVP}/
    Middleware/
  Infrastructure/
    Storage/
    CanvasExporters/
    Payments/
    Notifications/
  Console/Commands/ExpireEventsCommand.php
resources/js/
  Domains/
    Events/
    Editor/{Hooks,Components,Services}/
    Payments/{Components,Hooks}/
    RSVP/{Components,Hooks}/
  Pages/
    Web/Home.tsx
    App/{Dashboard,CreateEvent,Editor,Guests}.tsx
    Subdomains/{Show,Expired,Preparing}.tsx
  Types/{CanvasData,EventDTO,Layer,PaymentDTO}.ts
routes/{web,subdomains,console}.php
```

الأسماء بين الأقواس تختصر مجلدات أو ملفات منفصلة. Users وWebhooks وEvents في React توسعات مقترحة لتغطية متطلبات الحسابات والدفع، دون نقل منطق الأعمال إلى Http.

## المسؤوليات

- Controllers: استقبال Form Request، إنشاء DTO، استدعاء Action، إرجاع الاستجابة. ممنوع الاستعلامات أو حساب السعر أو تغيير الحالات داخلها.
- Pages الخاصة بـ GET ترجع Inertia::render. للحفظ والتحويلات تستخدم استجابات Redirect المتوافقة مع Inertia. الـ Webhook يعيد HTTP acknowledgment، وتسليم الملفات يعيد استجابة ملف. هذه استثناءات بروتوكولية لازمة لقاعدة «كل Controller يعيد Inertia» وتحتاج اعتماداً قبل كتابة تلك المسارات.
- Actions: عملية أعمال واحدة، transactions وحدود التزام واضحة، وتفويض للخدمات عند الحاجة.
- DTOs: بيانات typed غير قابلة للتعديل حيث يناسب، لا تعتمد على Request ولا تكشف Eloquent للواجهة.
- Services: خوارزميات slug، حساب الصلاحية، إعداد Canvas، معالجة العلامة المائية.
- Gateways في Payments: عقود البوابات؛ التنفيذ والاتصال الخارجي في Infrastructure/Payments.
- Jobs: تشغيل التصدير عبر عقد CanvasExporter؛ لا تعيد تنفيذ الدفع.
- Policies: التحقق من ملكية المناسبة؛ guest requests تستخرج المناسبة من المضيف الموثوق ولا تقبل event_id من العميل.

## قواعد ثابتة

declare(strict_types=1) وتواقيع كاملة لكل ملفات PHP الجديدة؛ TypeScript strict بلا any؛ Form Requests؛ Eloquent casts وenums؛ أسرار الخادم في الإعدادات؛ لا أسرار ضمن Inertia props؛ لا منطق دفع أو صلاحيات داخل React.

المصادقة في تطبيق Inertia عبر جلسة آمنة وCSRF؛ لا حاجة إلى توكن Sanctum للمتصفح في الإصدار الحالي. Cookie لوحة التحكم host-only ولا يشارك مع نطاقات الضيوف. Middleware للنطاقات يقبل label واحداً فقط ضمن نطاق ضيوف مضبوط، ويرفض app وwww وadmin وapi وmail وsupport وغيرها من الأسماء المحجوزة. تسجيل مسارات الضيوف مع عزل صريح عن المضيف الرئيسي.
