<x-mail.layout title="وصلتنا رسالتك" preheader="رد تلقائي لتأكيد الاستلام. سنردّ فور اطّلاعنا عليها.">
    <p style="margin:0 0 14px;">مرحباً {{ $name }}،</p>
    <p style="margin:0 0 6px;">وصلتنا رسالتك «{{ $title }}». هذا رد تلقائي لتأكيد الاستلام، وسنردّ عليك فور اطّلاعنا عليها.</p>
    <x-mail.button :href="$threadUrl">فتح رسائلي</x-mail.button>
    <p style="margin:0;color:#5b6358;font-size:14px;">ستجدين الرد في صفحة «رسائلي» بحسابك.</p>
</x-mail.layout>
