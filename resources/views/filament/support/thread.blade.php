<div class="space-y-3" dir="rtl">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ $thread->user->name }} · <bdi dir="ltr">{{ $thread->user->email }}</bdi> · {{ $thread->topic->getLabel() }}
    </p>
    <ul class="m-0 flex list-none flex-col gap-3 p-0">
        @foreach ($thread->messages as $message)
            <li @class([
                'rounded-lg p-3 text-sm',
                'bg-primary-50 dark:bg-primary-950 me-8' => $message->from_admin,
                'bg-gray-100 dark:bg-gray-800 ms-8' => ! $message->from_admin,
            ])>
                <div class="mb-1 flex justify-between gap-2 text-xs text-gray-500">
                    <strong>{{ $message->from_admin ? 'الفريق' : $thread->user->name }}</strong>
                    <time>{{ $message->created_at->timezone(config('methaq.invitation_timezone', 'Africa/Tripoli'))->format('d/m/Y H:i') }}</time>
                </div>
                <p class="m-0 whitespace-pre-line break-words">{{ $message->body }}</p>
            </li>
        @endforeach
    </ul>
    @if ($thread->status === \App\Domains\Users\Enums\SupportThreadStatus::Closed)
        <p class="text-sm font-semibold text-gray-500">هذه المحادثة مغلقة. أعيدي فتحها للرد.</p>
    @endif
</div>
