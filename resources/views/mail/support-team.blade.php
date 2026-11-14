<x-mail.layout :title="$isReply ? 'وصل رد جديد من عميلة' : 'وصلت رسالة جديدة من عميلة'" preheader="اقرئيها وردّي عليها من لوحة الإدارة.">
    <p style="margin:0 0 6px;">اقرئيها وردّي عليها من لوحة الإدارة:</p>
    <x-mail.button :href="$inboxUrl">فتح صندوق الرسائل</x-mail.button>
</x-mail.layout>
