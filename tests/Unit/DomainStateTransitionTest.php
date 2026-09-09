<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Editor\Enums\RenderStatus;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\WebhookStatus;
use PHPUnit\Framework\TestCase;

final class DomainStateTransitionTest extends TestCase
{
    public function test_event_transitions_do_not_reopen_expired_events(): void
    {
        self::assertTrue(EventStatus::Draft->canTransitionTo(EventStatus::Published));
        self::assertTrue(EventStatus::Published->canTransitionTo(EventStatus::Expired));
        self::assertFalse(EventStatus::Expired->canTransitionTo(EventStatus::Published));
        self::assertFalse(EventStatus::Published->canTransitionTo(EventStatus::Draft));
    }

    public function test_financial_terminal_states_cannot_be_rewritten(): void
    {
        self::assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Completed));
        self::assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Failed));
        self::assertFalse(OrderStatus::Completed->canTransitionTo(OrderStatus::Failed));
        self::assertFalse(OrderStatus::Failed->canTransitionTo(OrderStatus::Pending));
    }

    public function test_render_and_webhook_failures_can_return_to_their_queue(): void
    {
        self::assertTrue(RenderStatus::Processing->canTransitionTo(RenderStatus::Pending));
        self::assertTrue(RenderStatus::Failed->canTransitionTo(RenderStatus::Pending));
        self::assertFalse(RenderStatus::Completed->canTransitionTo(RenderStatus::Pending));
        self::assertTrue(WebhookStatus::Processing->canTransitionTo(WebhookStatus::Failed));
        self::assertTrue(WebhookStatus::Failed->canTransitionTo(WebhookStatus::Received));
        self::assertFalse(WebhookStatus::Processed->canTransitionTo(WebhookStatus::Received));
    }
}
