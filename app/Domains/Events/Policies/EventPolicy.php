<?php

declare(strict_types=1);

namespace App\Domains\Events\Policies;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Auth\Access\Response;

final class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isActive() && $event->user_id === $user->getKey();
    }

    public function update(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    /**
     * The owner changes her design only while the invitation is a draft. Once it has been published
     * (and after it expires) only the Methaq team edits it, through `administerDesign` (decision 2026-11-21).
     */
    public function editDesign(User $user, Event $event): Response
    {
        if (! $this->update($user, $event)) {
            return Response::deny();
        }

        return $event->isDesignLockedForOwner()
            ? Response::deny('دعوتك منشورة، فأُقفل تعديل التصميم. راسلي فريق ميثاق لطلب أي تعديل.')
            : Response::allow();
    }

    public function publish(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    /**
     * Reading an owner's unpublished draft is a deliberate, narrow administrator power: read-only, audited on every view,
     * and it needs the same confirmed two-factor guarantee as the admin panel. It never allows editing.
     */
    public function reviewDraft(User $user, Event $event): bool
    {
        return $this->isSecuredAdministrator($user);
    }

    /**
     * Editing a customer's design in the studio, before or after publishing, and refreshing the published
     * invitation from the saved draft. Only an active administrator with confirmed two-factor authentication;
     * every save and refresh is audited. The state rules for refreshing live in EventPublishEligibility.
     */
    public function administerDesign(User $user, Event $event): bool
    {
        return $this->isSecuredAdministrator($user);
    }

    /** Suspending or reinstating an invitation is an administrator's call, not an owner-policy override. */
    public function moderate(User $user, Event $event): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }

    private function isSecuredAdministrator(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin && $user->hasEnabledTwoFactorAuthentication();
    }
}
