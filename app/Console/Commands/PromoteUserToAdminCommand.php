<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Users\Actions\PromoteUserToAdminAction;
use Illuminate\Console\Command;
use Throwable;

final class PromoteUserToAdminCommand extends Command
{
    protected $signature = 'methaq:promote-admin {email : Email of an existing active user}';

    protected $description = 'Promote an existing active Methaq user through a trusted operational command';

    public function handle(PromoteUserToAdminAction $promoteUser): int
    {
        try {
            $user = $promoteUser->execute((string) $this->argument('email'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$user->email} is now an administrator. Two-factor authentication is required for admin pages.");

        return self::SUCCESS;
    }
}
