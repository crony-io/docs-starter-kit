<?php

namespace Tests\Feature;

use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Models\Page;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemConfig::create(['content_mode' => 'cms', 'setup_completed' => true]);
        SystemConfig::clearCache();
        User::factory()->create();
    }

    public function test_user_can_submit_helpful_feedback()
    {
        $page = Page::factory()->published()->create();

        $response = $this->post(route('feedback.store'), [
            'page_id' => $page->id,
            'is_helpful' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('feedback_responses', [
            'page_id' => $page->id,
            'is_helpful' => true,
        ]);
    }

    public function test_user_can_submit_not_helpful_feedback()
    {
        $page = Page::factory()->published()->create();

        $response = $this->post(route('feedback.store'), [
            'page_id' => $page->id,
            'is_helpful' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('feedback_responses', [
            'page_id' => $page->id,
            'is_helpful' => false,
        ]);
    }

    public function test_feedback_stores_ip_and_user_agent()
    {
        $page = Page::factory()->published()->create();

        $this->post(route('feedback.store'), [
            'page_id' => $page->id,
            'is_helpful' => true,
        ]);

        $feedback = FeedbackResponse::first();

        $this->assertNotNull($feedback->ip_address);
        $this->assertNotNull($feedback->user_agent);
    }

    public function test_feedback_can_include_form_data()
    {
        $page = Page::factory()->published()->create();
        $form = FeedbackForm::factory()->create();

        $this->post(route('feedback.store'), [
            'page_id' => $page->id,
            'feedback_form_id' => $form->id,
            'is_helpful' => false,
            'form_data' => [
                'comment' => 'This page needs more examples',
            ],
        ]);

        $feedback = FeedbackResponse::first();

        $this->assertEquals($form->id, $feedback->feedback_form_id);
        $this->assertEquals('This page needs more examples', $feedback->form_data['responses']['comment']);
    }

    public function test_feedback_requires_page_id()
    {
        $response = $this->post(route('feedback.store'), [
            'is_helpful' => true,
        ]);

        $response->assertSessionHasErrors('page_id');
    }

    public function test_feedback_requires_is_helpful()
    {
        $page = Page::factory()->published()->create();

        $response = $this->post(route('feedback.store'), [
            'page_id' => $page->id,
        ]);

        $response->assertSessionHasErrors('is_helpful');
    }

    public function test_feedback_validates_page_exists()
    {
        $response = $this->post(route('feedback.store'), [
            'page_id' => 99999,
            'is_helpful' => true,
        ]);

        $response->assertSessionHasErrors('page_id');
    }
}
