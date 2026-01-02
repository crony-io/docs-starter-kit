<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'feedback_form_id' => ['nullable', 'exists:feedback_forms,id'],
            'is_helpful' => ['required', 'boolean'],
            'form_data' => ['nullable', 'array'],
        ]);

        // Build structured form_data with responses and field snapshot
        $formData = $this->buildFormData(
            $validated['form_data'] ?? [],
            $validated['feedback_form_id'] ?? null
        );

        FeedbackResponse::create([
            'page_id' => $validated['page_id'],
            'feedback_form_id' => $validated['feedback_form_id'] ?? null,
            'is_helpful' => $validated['is_helpful'],
            'form_data' => $formData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Build structured form_data that includes both responses and field definitions
     */
    private function buildFormData(?array $responses, ?int $formId): ?array
    {
        // No responses provided
        if (empty($responses)) {
            return null;
        }

        // If using a custom form, include the form snapshot
        if ($formId) {
            $form = FeedbackForm::find($formId);
            if ($form) {
                return [
                    'form_name' => $form->name,
                    'fields' => $form->fields,
                    'responses' => $responses,
                ];
            }
        }

        // Default form (no custom form) - just store responses with a marker
        return [
            'form_name' => null,
            'fields' => [
                ['type' => 'textarea', 'label' => 'comment', 'required' => false],
            ],
            'responses' => $responses,
        ];
    }
}
