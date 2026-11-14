<div dir="rtl" class="space-y-2">
    @forelse ($offer->claims()->with(['user', 'event'])->latest('id')->get() as $claim)
        <div class="rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800">
            <strong>{{ $claim->user->name }}</strong>
            <bdi dir="ltr" class="text-gray-500">{{ $claim->user->email }}</bdi>
            <div class="text-gray-600 dark:text-gray-400">
                {{ $claim->event->title }} ·
                {{ $claim->created_at->timezone(config('methaq.invitation_timezone', 'Africa/Tripoli'))->format('d/m/Y H:i') }}
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">لم يستفد أحد من هذا العرض بعد.</p>
    @endforelse
</div>
