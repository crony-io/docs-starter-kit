<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeedbackFormRequest;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FeedbackResponse::query()
            ->with(['page:id,title,slug']);

        $this->applyFilters($query, $request);

        $responses = $query
            ->orderBy('created_at', 'desc')
            ->paginate(config('pagination.feedback', 20))
            ->withQueryString();

        $stats = $this->getStats();
        $pageStats = $this->getPageStats();
        $pages = Page::where('type', 'document')->get(['id', 'title', 'slug']);

        return Inertia::render('admin/feedback/Index', [
            'responses' => $responses,
            'stats' => $stats,
            'pageStats' => $pageStats,
            'pages' => $pages,
            'filters' => $request->only(['page_id', 'is_helpful', 'start_date', 'end_date']),
        ]);
    }

    public function forms(): Response
    {
        $forms = FeedbackForm::withCount('responses')->get();

        return Inertia::render('admin/feedback/Forms', [
            'forms' => $forms,
        ]);
    }

    public function createForm(): Response
    {
        return Inertia::render('admin/feedback/CreateForm');
    }

    public function storeForm(FeedbackFormRequest $request): RedirectResponse
    {
        FeedbackForm::create($request->validated());

        return to_route('admin.feedback.forms')->with('success', 'Feedback form created successfully.');
    }

    public function editForm(FeedbackForm $form): Response
    {
        return Inertia::render('admin/feedback/EditForm', [
            'form' => $form,
        ]);
    }

    public function updateForm(FeedbackFormRequest $request, FeedbackForm $form): RedirectResponse
    {
        $form->update($request->validated());

        return to_route('admin.feedback.forms')->with('success', 'Feedback form updated successfully.');
    }

    public function destroyForm(FeedbackForm $form): RedirectResponse
    {
        $form->delete();

        return to_route('admin.feedback.forms')->with('success', 'Feedback form deleted successfully.');
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $query = FeedbackResponse::query()
            ->with(['page:id,title,slug']);

        $this->applyFilters($query, $request);

        $responses = $query->orderBy('created_at', 'desc')->get();

        $format = $request->get('format', 'csv');

        if ($format === 'json') {
            return response()->json($responses);
        }

        $filename = 'feedback_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($responses) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID',
                'Page',
                'Is Helpful',
                'Form Name',
                'Responses',
                'IP Address',
                'Date',
            ]);

            foreach ($responses as $response) {
                $formName = $response->form_data['form_name'] ?? 'Default';
                $responses_data = $response->form_data['responses'] ?? [];

                fputcsv($file, [
                    $response->id,
                    $response->page?->title ?? 'N/A',
                    $response->is_helpful ? 'Yes' : 'No',
                    $formName,
                    json_encode($responses_data),
                    $response->ip_address,
                    $response->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy(FeedbackResponse $response): RedirectResponse
    {
        $response->delete();

        return back()->with('success', 'Feedback response deleted.');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('page_id')) {
            $query->where('page_id', $request->page_id);
        }

        if ($request->filled('is_helpful')) {
            $query->where('is_helpful', $request->is_helpful === 'true');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }

    private function getStats(): array
    {
        $total = FeedbackResponse::count();
        $helpful = FeedbackResponse::where('is_helpful', true)->count();
        $notHelpful = FeedbackResponse::where('is_helpful', false)->count();
        $todayCount = FeedbackResponse::whereDate('created_at', today())->count();

        return [
            'total' => $total,
            'helpful' => $helpful,
            'notHelpful' => $notHelpful,
            'helpfulPercentage' => $total > 0 ? round(($helpful / $total) * 100, 1) : 0,
            'todayCount' => $todayCount,
        ];
    }

    private function getPageStats(): array
    {
        return FeedbackResponse::query()
            ->selectRaw('page_id, COUNT(*) as total, SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count')
            ->groupBy('page_id')
            ->with('page:id,title,slug')
            ->get()
            ->map(function ($item) {
                return [
                    'page_id' => $item->page_id,
                    'page_title' => $item->page?->title ?? 'Unknown',
                    'page_slug' => $item->page?->slug ?? '',
                    'total' => $item->total,
                    'helpful_count' => $item->helpful_count,
                    'helpfulness_score' => $item->total > 0
                        ? round(($item->helpful_count / $item->total) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(10)
            ->toArray();
    }
}
