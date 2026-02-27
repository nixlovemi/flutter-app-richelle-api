<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_processing_endpoint_requires_token()
    {
        $response = $this->postJson('/api/queue/process');

        $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_queue_processing_endpoint_rejects_invalid_token()
    {
        $response = $this->postJson('/api/queue/process', [], [
            'X-Cron-Token' => 'invalid-token'
        ]);

        $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_queue_processing_endpoint_works_with_valid_token()
    {
        // Set a test token in config
        config(['queue.cron_token' => 'test-token-123']);

        $response = $this->postJson('/api/queue/process', [], [
            'X-Cron-Token' => 'test-token-123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'output'
                ]);
    }

    public function test_health_endpoint_works()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version'
                ])
                ->assertJson(['status' => 'ok']);
    }
}
