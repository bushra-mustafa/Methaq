<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class CloudMsgSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudmsg.enabled' => true, 'cloudmsg.instance_id' => 'inst_test', 'cloudmsg.webhook_secret' => 'test-secret']);
    }

    public function test_missing_invalid_and_stale_signatures_are_rejected_before_accessing_storage(): void
    {
        $this->postJson('/hooks/cloudmsg', [])->assertStatus(400);
        $this->request((string) now()->timestamp, 'bad')->assertUnauthorized();
        $timestamp = (string) now()->subMinutes(6)->timestamp;
        $this->request($timestamp, $this->signature($timestamp))->assertStatus(400);
        $timestamp = (string) now()->addMinutes(6)->timestamp;
        $this->request($timestamp, $this->signature($timestamp))->assertStatus(400);
    }

    public function test_wrong_instance_and_mismatched_event_identity_are_rejected(): void
    {
        $timestamp = (string) now()->timestamp;
        $this->request($timestamp, $this->signature($timestamp), 'evt_other')->assertStatus(400);
        config(['cloudmsg.instance_id' => 'inst_other']);
        $this->request($timestamp, $this->signature($timestamp))->assertForbidden();
    }

    public function test_disabling_integration_rejects_webhooks(): void
    {
        config(['cloudmsg.enabled' => false]);
        $this->postJson('/hooks/cloudmsg', [])->assertNotFound();
    }

    private function raw(): string
    {
        return '{"id":"evt_test","type":"message.received","data":{"instance_id":"inst_test"}}';
    }

    private function signature(string $timestamp): string
    {
        return 'v1='.hash_hmac('sha256', $timestamp.'.'.$this->raw(), 'test-secret');
    }

    private function request(string $timestamp, string $signature, string $eventId = 'evt_test'): TestResponse
    {
        return $this->call('POST', '/hooks/cloudmsg', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_CLOUDMSG_TIMESTAMP' => $timestamp, 'HTTP_X_CLOUDMSG_EVENT_ID' => $eventId, 'HTTP_X_CLOUDMSG_SIGNATURE' => $signature], $this->raw());
    }
}
