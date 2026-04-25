<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Models\DisciplineIncident;
use App\Models\Role;
use App\Models\User;
use App\Services\Discipline\DisciplineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisciplineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DisciplineIncident::class);
        $user = $request->user();

        $query = DisciplineIncident::query()
            ->with(['subject.role', 'reporter', 'decider'])
            ->orderByDesc('occurred_on');

        if (! $user->hasRole(Role::SYSTEM_ADMIN)) {
            $query->where('school_id', $user->school_id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return view('discipline.index', [
            'incidents' => $query->paginate(20)->withQueryString(),
            'filterStatus' => $status,
            'filterCategory' => $category,
            'categories' => DisciplineIncident::CATEGORIES,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DisciplineIncident::class);
        $user = $request->user();

        $subjects = User::query()
            ->with('role')
            ->when(! $user->hasRole(Role::SYSTEM_ADMIN), fn ($q) => $q->where('school_id', $user->school_id))
            ->where('id', '!=', $user->id)
            ->orderBy('first_name')
            ->get();

        return view('discipline.create', [
            'subjects' => $subjects,
            'categories' => DisciplineIncident::CATEGORIES,
            'preselected' => $request->query('subject_id'),
        ]);
    }

    public function store(Request $request, DisciplineService $service): RedirectResponse
    {
        $this->authorize('create', DisciplineIncident::class);
        $actor = $request->user();

        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:users,id'],
            'category' => ['required', 'in:'.implode(',', DisciplineIncident::CATEGORIES)],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:3000'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'severity' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $subject = User::findOrFail($data['subject_id']);
        if ($subject->school_id !== $actor->school_id && ! $actor->hasRole(Role::SYSTEM_ADMIN)) {
            abort(403, 'Cross-school logging is not allowed.');
        }

        $incident = $service->logIncident($actor, $subject, $data);

        return redirect()->route('discipline.show', $incident)->with('status', 'Incident logged.');
    }

    public function show(Request $request, DisciplineIncident $incident): View
    {
        $this->authorize('view', $incident);
        $incident->loadMissing(['subject.role', 'reporter', 'decider', 'school']);

        return view('discipline.show', [
            'incident' => $incident,
            'actor' => $request->user(),
        ]);
    }

    public function resolve(Request $request, DisciplineIncident $incident, DisciplineService $service): RedirectResponse
    {
        $this->authorize('decide', $incident);

        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:2000'],
        ]);

        $service->resolve($request->user(), $incident, $data['resolution']);

        return redirect()->route('discipline.show', $incident)->with('status', 'Incident resolved.');
    }

    public function dismiss(Request $request, DisciplineIncident $incident, DisciplineService $service): RedirectResponse
    {
        $this->authorize('decide', $incident);

        $data = $request->validate([
            'resolution' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->dismiss($request->user(), $incident, $data['resolution'] ?? null);

        return redirect()->route('discipline.show', $incident)->with('status', 'Incident dismissed.');
    }

    public function timeline(Request $request, User $subject, DisciplineService $service): View
    {
        $actor = $request->user();
        if (! $actor->can('viewTimeline', [DisciplineIncident::class, $subject])) {
            abort(403);
        }

        return view('discipline.timeline', [
            'subject' => $subject,
            'actor' => $actor,
            ...$service->timeline($subject),
        ]);
    }
}
