<x-mail.layout title="إعادة تعيين كلمة المرور" preheader="رابط لإنشاء كلمة مرور جديدة لحسابك في ميثاق.">
    <p style="margin:0 0 14px;">مرحباً {{ $name }}،</p>
    <p style="margin:0 0 6px;">وصلنا طلب لإعادة تعيين كلمة مرور حسابك. اضغطي على الزر لإنشاء كلمة مرور جديدة:</p>
    <x-mail.button :href="$url">إنشاء كلمة مرور جديدة</x-mail.button>
    <p style="margin:0 0 10px;color:#5b6358;font-size:14px;">الرابط صالح لمدة {{ $minutes }} دقيقة. إن لم تطلبي ذلك فتجاهلي الرسالة، ولن يتغير شيء في حسابك.</p>
    <p style="margin:0;color:#5b6358;font-size:13px;">إن لم يعمل الزر، انسخي هذا الرابط إلى المتصفح:<br><a href="{{ $url }}" style="color:#7a613b;word-break:break-all;" dir="ltr">{{ $url }}</a></p>
</x-mail.layout>
