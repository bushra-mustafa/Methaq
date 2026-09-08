<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_authentication_and_factory_use_the_domain_user(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertInstanceOf(User::class, User::factory()->make());
    }
}
