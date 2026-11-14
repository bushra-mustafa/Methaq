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
METHAQ_BANK_NAME=...
METHAQ_BANK_ACCOUNT_NAME=...
METHAQ_BANK_ACCOUNT_NUMBER=...
METHAQ_BANK_IBAN=...
```

بيانات الحساب البنكي (`METHAQ_BANK_*`) تظهر لصاحبة المناسبة في صفحة الدفع، ورفع القسائم يبقى مغلقاً حتى يُضبط `METHAQ_BANK_ACCOUNT_NUMBER`. وسعر كل قالب يُحدَّد من `/admin/templates` (السعر صفر يمنع الرفع).

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

## صور الدعوات المنشورة

عند النشر تُحفظ صورة البطاقة على القرص `public` (`shared/storage/app/public/renders/invitations/`) ويقرأها الضيف من `/storage/renders/...`. الـ Workflow ينشئ الرابط `public/storage` ← `../storage/app/public` في كل إصدار (المجلد غير مضمّن في Git). تأكدي أن Apache يسمح بتتبع الروابط الرمزية (`FollowSymLinks`) لمجلد `public`، وإلا ستظهر صورة البطاقة مكسورة بعد النشر. قبل أول نشر لهذه النسخة نفّذي الترحيلات يدوياً (`php artisan migrate --force`)، لأن الـ Workflow لا يرحّل وعمودا `is_featured` و`price_minor` وغيرهما مطلوبان للصفحة الرئيسية.

## الترحيلات والمهام المجدولة

الترحيلات متوقفة عمداً خارج Workflow. قبل أي ترحيل إنتاجي: خذي نسخة قاعدة البيانات، راجعي migration وتأثير القفل والبيانات، ثم شغّليها يدوياً في نافذة صيانة مناسبة.

أضيفي في cPanel ← **Cron Jobs** مهمة كل دقيقة، حتى يشتغل الأمر اليومي `methaq:expire-invitations` الساعة 00:05 بتوقيت طرابلس:

```bash
* * * * * cd /home/CPANEL_USER/apps/methaq/current && php artisan schedule:run >> /dev/null 2>&1
```

الأمر يحدّث حالة الدعوات المنتهية في لوحة التحكم فقط. منع الضيف بعد انتهاء الرابط ما يعتمدش عليه، لأن الخادم يتحقق منه مع كل طلب.

يستخدم المشروع حالياً queue وcache وsession بقاعدة البيانات افتراضياً. إن فعّلتِ Queue worker أو scheduler في cPanel، أضيفيهما كـCron/خدمة حسب ما تسمح به الاستضافة وراجعيهما بعد كل نشر؛ Workflow لا يفترض وجود Supervisor أو صلاحيات root.

## قائمة ما قبل الإطلاق (HTTPS والأمان والسيو والقياس)

كل بند هنا إعداد على السيرفر أو الاستضافة؛ الكود جاهز له ولا يغيّر شيئاً من الإنتاج وحده.

### HTTPS

1. شهادة SSL فعّالة للنطاق الرئيسي **ونطاقات الدعوات الفرعية** (`*.النطاق`)، لأن روابط الدعوات تُولَّد بـ`https` في الإنتاج.
2. في `.env` الإنتاج: `APP_ENV=production` و`APP_DEBUG=false` و`APP_URL=https://…` و`METHAQ_INVITATION_DOMAIN=` نطاق الدعوات، و`SESSION_SECURE_COOKIE=true`.
3. `METHAQ_FORCE_HTTPS` يعمل تلقائياً في الإنتاج: يحوّل http إلى https بإعادة توجيه 301 ويرسل HSTS (سنة، مع النطاقات الفرعية). لا تفعّلي HSTS preload قبل التأكد أن كل النطاقات الفرعية تعمل بـhttps.
4. خلف Cloudflare أو وسيط: اضبطي `TRUSTED_PROXIES` (قائمة عناوين مفصولة بفواصل، أو `*` إن كان كل الوصول يمر بالوسيط فقط) حتى يعرف Laravel أن الطلب الأصلي https. بدونها قد تحدث حلقة إعادة توجيه.
5. لا تغيّري DNS أو Cloudflare أو الشهادات من الكود؛ هذه خطوات يدوية في لوحة الاستضافة.

### الملفات والتخزين

- القسائم على القرص الخاص `storage/app/private` ولا يُخدَّم هذا القرص عبر `/storage` (`serve => false`).
- نفّذي `php artisan storage:link` أو تأكدي من رابط `public/storage` الذي ينشئه Workflow، فصور الدعوات المنشورة فقط تخرج منه.
- ملفات `public/.htaccess` تضيف تخزيناً مؤقتاً طويلاً للملفات الثابتة (يحتاج `mod_expires` و`mod_headers`، وهما اختياريان؛ بغيابهما يعمل الموقع بلا تخزين مؤقت).

### الصور والخطوط

- لكل صورة قالب أو مجموعة PNG نسخة WebP وAVIF بجانبها. عند إضافة قالب جديد حوّلي معاينته: `magick الملف.png -quality 82 -define webp:method=6 الملف.webp` وبنفس الاسم بامتداد `.avif` (يتطلب Pillow 11+ أو `avifenc`؛ ImageMagick لا يكتب AVIF هنا): `python -c "from PIL import Image; Image.open('x.png').convert('RGB').save('x.avif', quality=58)"`. يختبر `ThumbnailVariantsTest` وجود النسختين.
- خطوط الواجهة العامة تُحمَّل بصيغة WOFF2 مجزّأة (عربي ولاتيني أساسي). الملفات الأصلية TTF باقية لمحرر البطاقات والظرف.

### محركات البحث

- `/sitemap.xml` و`/robots.txt` يُبنيان من النطاق الفعلي. أرسلي `https://النطاق/sitemap.xml` في Google Search Console (Sitemaps ثم أضيفي رابطاً جديداً) وتحققي من ملكية النطاق هناك.
- روابط الدعوات وصفحات الحساب `noindex`، وأي نطاق فرعي غير النطاق الرئيسي يعيد `Disallow: /`.
- صورة المشاركة الافتراضية `public/brand/images/og-default.png` مركّبة من الشعار الرسمي على خلفية الموقع؛ اعتمديها أو استبدليها بتصميم رسمي بنفس الاسم (1200×630).

### القياس الإحصائي (اختياري، مطفأ افتراضياً)

لا يعمل إلا إذا اجتمع: `ANALYTICS_ENABLED=true` و`APP_ENV=production` و`PLAUSIBLE_DOMAIN=النطاق`، ويحتاج حساباً (اشتراكاً مدفوعاً أو استضافة ذاتية) في Plausible. يُحمَّل بعد موافقة الزائرة فقط، وفي الصفحات العامة فقط، وتستثني إعدادات `script.exclusions.js` صفحات الحساب والإدارة والدعوات. إن استضفتِ Plausible بنفسك فاضبطي `PLAUSIBLE_SCRIPT_URL` على سكربتك (يجب أن يدعم `data-exclude`).

### Cloudflare Turnstile (قرار مؤجل)

الحماية الحالية: حدود طلبات لكل عنوان، وحقل فخ مخفي، في التسجيل واستعادة كلمة المرور وردود الضيوف. إضافة Turnstile تحتاج مفاتيح من Cloudflare وتحميل سكربت طرف ثالث، فلم تُفعَّل؛ فعّليها فقط إن ظهر ضغط فعلي من الروبوتات.

## استكشاف الأخطاء

| العَرَض | الإجراء الآمن |
|---|---|
| فشل SSH أو `Host key verification failed` | قارني `DEPLOY_SSH_KNOWN_HOSTS` مع بصمة الدعم؛ لا تعطلّي StrictHostKeyChecking. |
| فشل `composer` أو PHP | أكدي مسار وإصدار PHP CLI وComposer من cPanel؛ لا تنقلي `vendor` من GitHub بين منصات مختلفة كحل سريع. |
| فشل إنشاء `current` | الاستضافة لا تسمح بالروابط الرمزية أو `mv -T`؛ أوقفي النشر واطلبي مساراً مدعوماً من الدعم. |
| فشل `/up` | سيحاول Workflow الرجوع تلقائياً. افحصي `.env` و`storage/logs/laravel.log` وdocument root. |
| خطأ صلاحيات `storage` | تأكدي أن حساب SSH نفسه يملك `shared/storage`، ولا تستخدمي `chmod 777`. |

## الترويسات الأمنية وقائمة المضيفين المسموحة

- كل رد يحمل `X-Content-Type-Options` و`X-Frame-Options` و`Referrer-Policy` و`Permissions-Policy` و`Cross-Origin-Opener-Policy`. الصفحات العامة وصفحات التطبيق تحمل أيضاً `Content-Security-Policy` صارمة (سكربتات من الموقع نفسه فقط، وأصل Plausible فقط إذا فُعِّلت التحليلات). لوحة `/admin` تحمل الترويسات الأساسية فقط لأن Filament يستعمل سكربتات مضمّنة. لا تُرسل السياسة في البيئة `local`.
- في الإنتاج يقبل التطبيق المضيفين `APP_URL` و`METHAQ_INVITATION_DOMAIN` ونطاقاتهما الفرعية فقط، وأي `Host` آخر يحصل على 400 (يمنع تسميم روابط إعادة كلمة المرور). إن احتجتِ مضيفاً إضافياً ضعيه في `METHAQ_TRUSTED_HOSTS` (قائمة مفصولة بفواصل).
- **تحقق قبل النشر:** `APP_URL` هو نطاق الموقع الحقيقي، وليس `localhost`، وإلا يرفض التطبيق كل الطلبات.


## Cloudflare Turnstile على التسجيل

يحمي التسجيل وطلب إعادة كلمة المرور من الحسابات الآلية، ويعمل فقط عند ضبط المفتاحين. بدونهما تبقى الحماية الحالية (الحقل المخفي وتحديد المعدل).

1. في لوحة Cloudflare: Turnstile ثم Add widget. أضيفي نطاق الموقع (وأي نطاق تجريبي)، ونمط الودجت **Managed**.
2. ضعي المفتاحين في `.env` على السيرفر: `TURNSTILE_SITE_KEY` (علني) و`TURNSTILE_SECRET_KEY` (سري، لا يُرفع إلى Git)، ثم `php artisan config:clear` (أو `config:cache`).
3. التحقق يتم على الخادم. إذا تعذّر الوصول إلى Cloudflare يُرفض الطلب (يفشل مغلقاً) ويظهر للمستخدمة «تعذّر التحقق».
4. سياسة `Content-Security-Policy` تسمح بـ `challenges.cloudflare.com` (سكربت وإطار واتصال) تلقائياً عند التفعيل فقط.
5. للتجربة المحلية تقدّم Cloudflare مفاتيح اختبار علنية: الموقع `1x00000000000000000000AA` (ينجح دائماً) والسري `1x0000000000000000000000000000000AA`. لا تستعمليها في الإنتاج.
6. تسجيل الدخول وردود الضيوف لا يطلبان التحقق عمداً، ويبقى لهما تحديد المعدل والحقل المخفي.

## الرسائل ووسائل التواصل

بعد `php artisan migrate --force` (ثلاثة جداول جديدة: `contact_channels` و`support_threads` و`support_messages`) نفّذي مرة واحدة `php artisan db:seed --class=ContactChannelSeeder --force` لإضافة البريد `info@methq.ly`. يمكن إعادة تشغيله دون تكرار. بعدها تُدار وسائل التواصل من `/admin/contact-channels` والرسائل من `/admin/support-threads`. متغيرات اختيارية: `METHAQ_MAX_OPEN_SUPPORT_THREADS` (الافتراضي 5) و`METHAQ_SUPPORT_INBOX` (البريد الذي تصله إشعارات الرسائل بعنوان الرسالة فقط؛ الافتراضي methq98@gmail.com، وتركه فارغاً يوقف الإشعار). الرد التلقائي للعميلة والإشعار يحتاجان إعدادات `MAIL_*` حقيقية في `shared/.env` (الافتراضي `MAIL_MAILER=log` لا يرسل شيئاً).

## قائمة أول تشغيل (بالترتيب)

1. في `shared/.env`: `APP_ENV=production` و`APP_DEBUG=false` و`APP_KEY` و`APP_URL` و`METHAQ_INVITATION_DOMAIN` و`SESSION_SECURE_COOKIE=true` و`LOG_LEVEL=warning` و`LOG_STACK=daily` و`TRUSTED_PROXIES` (خلف Cloudflare) و`METHAQ_BANK_*` (احتياطي فقط، والأفضل إضافة الحسابات من `/admin/bank-accounts`)، وإعدادات البريد الحقيقية `MAIL_MAILER=smtp` مع `MAIL_HOST` و`MAIL_PORT` و`MAIL_USERNAME` و`MAIL_PASSWORD` و`MAIL_FROM_ADDRESS` من نطاق ميثاق (وSPF وDKIM للنطاق). ومعها `METHAQ_SUPPORT_INBOX` (بريد إشعارات الرسائل؛ فارغ يوقف الإشعار) والمفتاحان `TURNSTILE_*`.
2. بعد اكتمال النشر: `php artisan migrate --force` ثم `php artisan db:seed --force` (يضيف القوالب والصوتيات وبريد التواصل، وإعادته آمنة) ثم `php artisan config:cache`.
3. سجّلي حسابك من الموقع، ثم `php artisan methaq:promote-admin بريدك`، ثم فعّلي المصادقة الثنائية من إعدادات الحساب قبل فتح `/admin`.
4. من `/admin/templates` ضعي **سعراً لكل قالب** تريدين عرضه. القالب بسعر 0 مخفي عن الصفحة الرئيسية وصفحة القوالب وإنشاء المناسبة.
5. أرسلي «نسيت كلمة المرور» لحسابك للتأكد أن البريد يصل فعلاً (فشل البريد لا يُظهر خطأ في الموقع).
6. جرّبي رابط دعوة حقيقياً على نطاق فرعي بـ https (شهادة تغطي `*.النطاق`، وCloudflare بنمط Full (strict))، وتأكدي أن `www` يتحول إلى النطاق الرئيسي.
7. جدولي نسخاً احتياطياً لقاعدة البيانات ولمجلد `shared/storage` (القسائم الخاصة والبطاقات المنشورة)، وجرّبي استعادة واحدة.
8. اضبطي cron: `* * * * * cd .../current && php artisan schedule:run`.

بعد كل إصدار يحمل ترحيلاً: خذي نسخة احتياطية، ثم `php artisan migrate --force`، ثم `config:cache` إن تغيّر `.env`.

الحسابات البنكية: بعد `migrate --force` أضيفي حساباً أو أكثر من `/admin/bank-accounts` (الحسابات الظاهرة تُعرض للعميلة في صفحة الدفع بالترتيب). إن لم يوجد حساب مخزّن تُستعمل `METHAQ_BANK_*`، وإن لم تكن مضبوطة تُقفل صفحة الدفع. كل تعديل يُسجَّل في سجل التدقيق برقم مقنّع (آخر 4 أرقام).
