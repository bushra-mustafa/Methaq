<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Events\Actions\ExpirePublishedInvitationsAction;
use Illuminate\Console\Command;

final class ExpireInvitationsCommand extends Command
{
    protected $signature = 'methaq:expire-invitations';

    protected $description = 'Mark published invitations whose guest link has closed as expired';

    public function handle(ExpirePublishedInvitationsAction $expireInvitations): int
    {
        $count = $expireInvitations->execute();
        $this->info("{$count} invitation(s) marked as expired.");

        return self::SUCCESS;
    }
}
