# تأسيس واجهة ميثاق — 2026-09-09

تم تثبيت React/React DOM 19.2.8، Inertia React 2.3.27، inertia-laravel 2.0.26، Fabric 6.9.1 وTypeScript 5.9.3. الإصدارات مقيدة بالفروع الرئيسية المطلوبة ومثبتة بدقة في lockfiles.

## التنفيذ

- مدخل resources/js/app.tsx مع StrictMode وحل صفحات Inertia بشكل lazy وفشل تحميل مفهوم للمستخدم.
- Vite مع React plugin وTailwind 4، وإعداد TS strict دون any في كود المشروع الجديد.
- HandleInertiaRequests ضمن web middleware، وBlade root مع @viteReactRefresh و@inertia و@inertiaHead.
- HomeController يعيد Inertia::render فقط؛ صفحة Web/Home بداية بسيطة بالهوية المعتمدة، لا محرر أو تسجيل دخول وهمي.
- الخطوط المحلية والألوان والشعارات والأيقونات مربوطة؛ إزالة صفحة Laravel الافتراضية ومدخل JavaScript السابق.
- Fabric مثبت وفحص استيراده وإنشاء عنصر وتسلسله؛ لم ينفذ Canvas editor أو useCanvas بعد، ولا تحمل الصفحة الرئيسية مكتبة Fabric بلا حاجة.

## التحقق

npm run typecheck وnpm run build ناجحان. فحوص Laravel المحددة: 4 اختبارات، 19 assertion؛ تشمل GET وطلب بروتوكول Inertia. Composer validate وPint ناجحان. فحص المتصفح محلياً أظهر الصفحة والشعار على سطح المكتب و390×844، دون console errors. روابط الخطوط المطلقة تقدم من public وقت التشغيل؛ ظهورها تحقق في المتصفح.

لا تغييرات في بيانات قاعدة التطوير. اختبارات MySQL لم تعد لأن هذه الخطوة لا تعدل schema. لا رفع إنتاجي. صفحة المعاينة محلية، وخادم Laravel يمكن تشغيله من README.

## التالي

تجربة البطاقة البودرية واللمعة والعربية والإنجليزية، ثم تثبيت عقود التصميم. الحسابات والصلاحيات والمحرر والدفع لم تنفذ بهذه الخطوة.

## المراجع

- https://inertiajs.com/docs/v2/installation/server-side-setup
- https://fabricjs.com/docs/upgrading/upgrading-to-fabric-60/
