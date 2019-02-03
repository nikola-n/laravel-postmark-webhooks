<?php

namespace Tests\Database;

use Tests\TestCase;
use Mvdnbrk\PostmarkWebhooks\PostmarkWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostmarkWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_postmark_webhook()
    {
        $payload = [
            'RecordType' => 'Open',
            'FirstOpen' => true,
        ];

        PostmarkWebhook::forceCreate([
            'email' => 'john@example.com',
            'message_id' => '9999-9999-9999-9999-9999',
            'record_type' => 'open',
            'payload' => $payload,
        ]);

        $this->assertCount(1, PostmarkWebhook::all());
        tap(PostmarkWebhook::first(), function ($webhook) use ($payload) {
            $this->assertEquals('9999-9999-9999-9999-9999', $webhook->message_id);
            $this->assertEquals('open', $webhook->record_type);
            $this->assertJson($webhook->getOriginal('payload'));
            $this->assertJsonStringEqualsJsonString(json_encode($payload), $webhook->getOriginal('payload'));
        });
    }

    /** @test */
    public function it_can_create_a_postmark_webhook_from_payload()
    {
        $payload = [
            'Recipient' => 'john@example.com',
            'MessageID' => '9999-9999-9999-9999-9999',
            'RecordType' => 'Open',
        ];

        PostmarkWebhook::createOrNewFromPayload($payload);

        $this->assertCount(1, PostmarkWebhook::all());
        tap(PostmarkWebhook::first(), function ($webhook) use ($payload) {
            $this->assertEquals('9999-9999-9999-9999-9999', $webhook->message_id);
            $this->assertEquals('open', $webhook->record_type);
            $this->assertEquals('john@example.com', $webhook->email);
            $this->assertJson($webhook->getOriginal('payload'));
            $this->assertJsonStringEqualsJsonString(json_encode($payload), $webhook->getOriginal('payload'));
        });
    }

    /** @test */
    public function it_returns_a_new_postmark_webhook_instance_when_logging_to_database_is_disabled()
    {
        config(['postmark-webhooks.log.enabled' => false]);

        $payload = [
            'Recipient' => 'john@example.com',
            'MessageID' => '9999-9999-9999-9999-9999',
            'RecordType' => 'Open',
        ];

        $webhook = PostmarkWebhook::createOrNewFromPayload($payload);

        $this->assertCount(0, PostmarkWebhook::all());

        $this->assertEquals('9999-9999-9999-9999-9999', $webhook->message_id);
        $this->assertEquals('open', $webhook->record_type);
        $this->assertEquals('john@example.com', $webhook->email);
        $this->assertEquals($payload, $webhook->payload);
    }
}