<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Services\Portal\PortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewInboxController extends Controller
{
    public function __construct(private readonly PortalService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ResultReviewRequest::class);
        $user = $request->user();

        $query = ResultReviewRequest::query()
            ->with(['attempt.exam', 'student', 'submitter', 'decider'])
            ->orderByDesc('created_at');

        if (! $user->hasRole(Role::SYSTEM_ADMIN)) {
            $query->where('school_id', $user->school_id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('academic.reviews.index', [
            'reviews' => $query->paginate(25)->withQueryString(),
            'filterStatus' => $status,
        ]);
    }

    public function show(Request $request, ResultReviewRequest $review): View
    {
        $this->authorize('view', $review);

        return view('academic.reviews.show', [
            'review' => $review->load(['attempt.exam', 'student', 'submitter', 'decider']),
            'actor' => $request->user(),
        ]);
    }

    public function acknowledge(Request $request, ResultReviewRequest $review): RedirectResponse
    {
        $this->authorize('decide', $review);
        $this->service->acknowledgeReview($request->user(), $review);

        return redirect()->route('academic.reviews.show', $review)->with('status', 'Review acknowledged.');
    }

    public function resolve(Request $request, ResultReviewRequest $review): RedirectResponse
    {
        $this->authorize('decide', $review);
        $data = $request->validate(['feedback' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->service->resolveReview($request->user(), $review, $data['feedback']);

        return redirect()->route('academic.reviews.show', $review)->with('status', 'Review resolved.');
    }

    public function reject(Request $request, ResultReviewRequest $review): RedirectResponse
    {
        $this->authorize('decide', $review);
        $data = $request->validate(['feedback' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->service->rejectReview($request->user(), $review, $data['feedback']);

        return redirect()->route('academic.reviews.show', $review)->with('status', 'Review rejected.');
    }
}
