<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\MarkSupportThreadReadAction;
use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Models\SupportMessage;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowSupportThreadController extends Controller
{
    public function __invoke(Request $request, SupportThread $thread, MarkSupportThreadReadAction $markRead): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        // Administrators answer from the admin panel; here only the owner reads her own conversation.
        abort_unless($thread->user_id === $user->getKey(), 404);
        $markRead->execute($user, $thread);

        return Inertia::render('App/Support/Show', [
            'thread' => [
                'id' => (int) $thread->getKey(),
                'topicLabel' => $thread->topic->getLabel(),
                'subject' => $thread->subject,
                'closed' => $thread->status === SupportThreadStatus::Closed,
            ],
            'messages' => $thread->messages()->get()->map(fn (SupportMessage $message): array => [
                'id' => (int) $message->getKey(),
                'fromTeam' => $message->from_admin,
                'body' => $message->body,
                'createdAt' => $message->created_at->toIso8601String(),
            ])->all(),
        ]);
    }
}
