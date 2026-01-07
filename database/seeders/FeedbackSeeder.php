<?php

namespace Database\Seeders;

use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Models\Page;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        FeedbackForm::create([
            'name' => 'Positive Feedback',
            'trigger_type' => 'positive',
            'fields' => [
                [
                    'type' => 'textarea',
                    'label' => 'What did you find most helpful?',
                    'required' => false,
                    'options' => [],
                ],
                [
                    'type' => 'rating',
                    'label' => 'How would you rate this page?',
                    'required' => false,
                    'options' => [],
                ],
            ],
            'is_active' => true,
        ]);

        FeedbackForm::create([
            'name' => 'Negative Feedback',
            'trigger_type' => 'negative',
            'fields' => [
                [
                    'type' => 'radio',
                    'label' => 'What was the issue?',
                    'required' => true,
                    'options' => [
                        'Information was unclear',
                        'Information was incomplete',
                        'Information was outdated',
                        'Information was incorrect',
                        'Other',
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Please provide more details',
                    'required' => false,
                    'options' => [],
                ],
                [
                    'type' => 'email',
                    'label' => 'Your email (optional)',
                    'required' => false,
                    'options' => [],
                ],
            ],
            'is_active' => true,
        ]);

        $this->seedSampleResponses();
    }

    private function seedSampleResponses(): void
    {
        $pages = Page::where('type', 'document')->take(5)->get();

        if ($pages->isEmpty()) {
            return;
        }

        foreach ($pages as $page) {
            $responseCount = rand(3, 8);

            for ($i = 0; $i < $responseCount; $i++) {
                FeedbackResponse::create([
                    'page_id' => $page->id,
                    'feedback_form_id' => null,
                    'is_helpful' => rand(0, 100) > 30,
                    'form_data' => rand(0, 1) ? ['responses' => ['comment' => $this->getRandomComment()]] : null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }

    private function getRandomComment(): string
    {
        $comments = [
            'Very helpful, thanks!',
            'Could use more examples.',
            'Clear and concise.',
            'This answered my question.',
            'The code samples were great.',
            'Needs more detail on edge cases.',
            'Perfect explanation!',
        ];

        return $comments[array_rand($comments)];
    }
}
