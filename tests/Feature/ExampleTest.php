<?php

namespace Tests\Feature;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemConfig::create(['content_mode' => 'cms', 'setup_completed' => true]);
        SystemConfig::clearCache();
        User::factory()->create();
    }

    public function test_returns_a_successful_response()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('docs.index'));
    }
}
