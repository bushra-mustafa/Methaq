<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListSupportThreadsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $threads = SupportThread::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get()
            ->map(fn (SupportThread $thread): array => [
                'id' => (int) $thread->getKey(),
                'topic' => $thread->topic->value,
                'topicLabel' => $thread->topic->getLabel(),
                'subject' => $thread->subject,
                'status' => $thread->status->value,
                'unread' => $thread->customer_unread,
                'lastMessageAt' => $thread->last_message_at->toIso8601String(),
            ])
            ->all();

        return Inertia::render('App/Support/Index', [
            'threads' => $threads,
            'topics' => array_map(fn (SupportTopic $topic): array => ['value' => $topic->value, 'label' => $topic->getLabel()], SupportTopic::cases()),
        ]);
    }
}
