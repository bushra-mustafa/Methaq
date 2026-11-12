# النشر الآمن على Libyan Spider / cPanel

هذا الدليل يصف النشر التلقائي من فرع `main` إلى `methq.ly`. لا يغيّر Workflow قاعدة البيانات تلقائياً، ولا يشغّل `php artisan migrate --force`، ولا يحذف إصدارات سابقة أو ملفات مشتركة.

## ما الذي يفعله Workflow

ملف `.github/workflows/deploy.yml` يعمل بعد كل push إلى `main`:

1. يثبت PHP 8.4 وNode 22 داخل GitHub Actions، ثم يشغّل Pint وفحوص TypeScript واختبارات الواجهة وبناء Vite واختبارات Laravel على MySQL 8.4.
2. يحزم المصدر الذي اجتاز الفحوص مع `public/build` الناتج من Vite. لا تدخل `.env` أو `vendor` أو `node_modules` أو الاختبارات أو ملفات `storage` المتغيرة (السجلات والجلسات والكاش) في الحزمة. أصول المكتبة الأصلية في `storage/app/private/editor` (الخطوط وملفات SVG) جزء من المستودع، فتُنسخ إلى `shared/storage` مع كل نشر بالإضافة والاستبدال فقط، دون حذف.
3. يرفع الحزمة عبر SSH إلى إصدار جديد داخل `releases/<commit-sha>`، ويربطه بـ`.env` و`storage` المشتركين، ثم يشغّل Composer وذاكرات Laravel المؤقتة قبل تفعيل الإصدار.
4. يبدّل رابط `current` فقط بعد اكتمال التجهيز، ثم يفحص `/up`. إذا أخفق الفحص وكان إصدار سابق موجوداً، يعيد `current` إلى الإصدار السابق.

لا يحذف Workflow الإصدارات القديمة؛ تنظيفها قرار يدوي بعد التحقق من الاستقرار.

## متطلبات cPanel قبل التفعيل

لا يمكن افتراض هذه الصلاحيات على الاستضافة المشتركة. افتحي Terminal في cPanel أو اطلبي من الدعم تأكيد التالي للحساب الذي سيستقبل النشر:

```bash
php -v
composer --version
ssh -V
command -v tar
command -v mv
mv --help | grep -- ' -T'
```

المطلوب فعلياً:

- PHP CLI بإصدار **8.4.1 أو أحدث** (مكتبات Symfony 8.1 المقفلة في `composer.lock` تتطلبه)، مع امتدادات Laravel و`pdo_mysql`.
- Composer متاح للمستخدم نفسه.
- SSH/SFTP outbound من GitHub Actions إلى المنفذ المسموح به.
- أوامر `tar` و`ln -s` و`mv -T` مسموحة.
- إمكانية جعل document root للنطاق `methq.ly` يشير إلى:
  `/home/CPANEL_USER/apps/methaq/current/public`
- MySQL وقاعدة البيانات وجداول Laravel المطلوبة موجودة مسبقاً.

إذا لم يسمح cPanel بتغيير document root إلى `current/public` أو لا يسمح بالروابط الرمزية، أوقفي تفعيل Workflow: نحتاج مسار نشر مختلفاً يحدده الدعم، وليس من الآمن تخمينه.

## تجهيز المسارات والبيئة على السيرفر

استبدلي `CPANEL_USER` باسم حساب الاستضافة فقط في Terminal، ولا تضعي قيمة حقيقية في المستودع:

```bash
mkdir -p /home/CPANEL_USER/apps/methaq/shared/storage/framework/cache
mkdir -p /home/CPANEL_USER/apps/methaq/shared/storage/framework/sessions
mkdir -p /home/CPANEL_USER/apps/methaq/shared/storage/framework/views
mkdir -p /home/CPANEL_USER/apps/methaq/shared/storage/logs
touch /home/CPANEL_USER/apps/methaq/shared/.env
chmod 600 /home/CPANEL_USER/apps/methaq/shared/.env
```

ضعي القيم الإنتاجية في `shared/.env` باستخدام محرر cPanel الآمن أو جلسة SSH؛ لا تنسخي ملف `.env` المحلي تلقائياً. يجب أن يحتوي الملف، على الأقل:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://methq.ly
METHAQ_INVITATION_DOMAIN=methq.ly
SESSION_SECURE_COOKIE=true
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

راجعي اسم نطاق الدعوات الفرعية قبل الإطلاق؛ `METHAQ_INVITATION_DOMAIN=methq.ly` يحتاج DNS wildcard وشهادة TLS مناسبة، وهذان ليسا جزءاً من هذا Workflow.

## مفتاح SSH الآمن

أنشئي زوج مفاتيح مخصصاً للنشر على جهاز موثوق، بدون كلمة مرور تفاعلية، ثم ضعي المفتاح العام فقط في `~/.ssh/authorized_keys` لحساب cPanel:

```bash
ssh-keygen -t ed25519 -C "methaq-github-deploy" -f ~/.ssh/methaq_github_deploy
```

لا ترسلي المفتاح الخاص في المحادثة ولا تضعيه في أي ملف بالمشروع. انسخي محتوى `methaq_github_deploy.pub` إلى الخادم، وخذي بصمة المضيف من لوحة الاستضافة أو الدعم، وليس عبر `ssh-keyscan` غير موثوق.

## GitHub Secrets وVariable

من المستودع: **Settings → Secrets and variables → Actions**. أضيفي Secrets التالية:

| الاسم | القيمة |
|---|---|
| `DEPLOY_SSH_PRIVATE_KEY` | كامل المفتاح الخاص ED25519، متعدد الأسطر |
| `DEPLOY_SSH_KNOWN_HOSTS` | سطر known_hosts الموثق من الاستضافة، مثل `102.213.180.6 ssh-ed25519 AAAA...` |
| `DEPLOY_HOST` | `102.213.180.6` أو اسم SSH المعتمد من الاستضافة |
| `DEPLOY_PORT` | منفذ SSH الذي تؤكده الاستضافة، غالباً `22` |
| `DEPLOY_USER` | اسم مستخدم cPanel/SSH فقط |
| `DEPLOY_PATH` | مسار التطبيق المطلق، مثل `/home/CPANEL_USER/apps/methaq` |

وأضيفي Variable غير سرياً:

| الاسم | القيمة |
|---|---|
| `DEPLOY_HEALTHCHECK_URL` | `https://methq.ly/up` |

لا تضيفي قاعدة البيانات أو `.env` كـ GitHub Secrets في هذا الأسلوب؛ تبقى على الخادم في `shared/.env` ولا تنتقل مع كل نشر.

يفضل إنشاء GitHub Environment باسم `production` وتفعيل Required reviewers قبل أول نشر؛ الـWorkflow يستخدم هذا الاسم بالفعل، فيتوقف قبل تفعيل الإصدار إلى أن تتم الموافقة من GitHub.

## أول نشر والتحقق

1. تأكدي من أن `main` هو الفرع الذي تريدين إطلاقه، ثم ادمجي إليه التغيير بعد مراجعته.
2. من تبويب **Actions** افتحي `Deploy Production` وتابعي مرحلتي `Verify release candidate` و`Deploy verified release`.
3. بعد نجاح Health check افتحي `https://methq.ly/up` ثم الصفحة الرئيسية وسجلي الدخول واختبري محرر بطاقة موجودة.
4. راجعي `storage/logs/laravel.log` على الخادم إن فشل أي جزء.

Workflow `Quality` الموجود يبقى فحصاً مستقلاً للـpull requests و`main`. Workflow النشر يعيد الفحوص عمداً على commit الذي سيصل للإنتاج.

## Rollback

عند فشل Health check، يعيد Workflow تلقائياً رابط `current` إلى الإصدار السابق إن كان موجوداً. إذا احتجتِ رجوعاً يدوياً، لا تحذفي شيئاً؛ حددي مجلد إصدار معروفاً ثم نفذي داخل `DEPLOY_PATH`:

```bash
ln -s /home/CPANEL_USER/apps/methaq/releases/COMMIT_SHA current.rollback
mv -Tf current.rollback current
```

بعد الرجوع، افحصي `/up` وسجل Laravel. لا تشغلي ترحيلات عكسية على قاعدة الإنتاج إلا بعد مراجعة مستقلة لأثرها.

## الترحيلات والمهام المجدولة

الترحيلات متوقفة عمداً خارج Workflow. قبل أي ترحيل إنتاجي: خذي نسخة قاعدة البيانات، راجعي migration وتأثير القفل والبيانات، ثم شغّليها يدوياً في نافذة صيانة مناسبة.

يستخدم المشروع حالياً queue وcache وsession بقاعدة البيانات افتراضياً. إن فعّلتِ Queue worker أو scheduler في cPanel، أضيفيهما كـCron/خدمة حسب ما تسمح به الاستضافة وراجعيهما بعد كل نشر؛ Workflow لا يفترض وجود Supervisor أو صلاحيات root.

## استكشاف الأخطاء

| العَرَض | الإجراء الآمن |
|---|---|
| فشل SSH أو `Host key verification failed` | قارني `DEPLOY_SSH_KNOWN_HOSTS` مع بصمة الدعم؛ لا تعطلّي StrictHostKeyChecking. |
| فشل `composer` أو PHP | أكدي مسار وإصدار PHP CLI وComposer من cPanel؛ لا تنقلي `vendor` من GitHub بين منصات مختلفة كحل سريع. |
| فشل إنشاء `current` | الاستضافة لا تسمح بالروابط الرمزية أو `mv -T`؛ أوقفي النشر واطلبي مساراً مدعوماً من الدعم. |
| فشل `/up` | سيحاول Workflow الرجوع تلقائياً. افحصي `.env` و`storage/logs/laravel.log` وdocument root. |
| خطأ صلاحيات `storage` | تأكدي أن حساب SSH نفسه يملك `shared/storage`، ولا تستخدمي `chmod 777`. |
