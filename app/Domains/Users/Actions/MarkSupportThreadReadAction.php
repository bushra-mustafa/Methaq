<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/** Clears the unread mark of whoever opens the conversation (the owner, or an administrator). */
final class MarkSupportThreadReadAction
{
    public function execute(User $viewer, SupportThread $thread): void
    {
        Gate::forUser($viewer)->authorize('view', $thread);

        $column = $viewer->role === UserRole::Admin && $thread->user_id !== $viewer->getKey() ? 'admin_unread' : 'customer_unread';

        if ($thread->{$column}) {
            SupportThread::query()->whereKey($thread->getKey())->update([$column => false]);
        }
    }
}
