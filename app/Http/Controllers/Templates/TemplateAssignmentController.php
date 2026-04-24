<?php

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateAssignmentRequest;
use App\Models\Role;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Models\User;
use App\Services\Templates\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TemplateAssignment::class);

        $user = $request->user();
        $query = TemplateAssignment::query()->latest();
        if (! $user->hasRole(Role::SYSTEM_ADMIN)) {
            $query->where('school_id', $user->school_id);
        }

        return view('templates.assignments.index', [
            'assignments' => $query->with(['template', 'creator', 'assignee'])->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TemplateAssignment::class);

        $user = $request->user();
        $selectedTemplateId = $request->integer('template_id') ?: null;

        return view('templates.assignments.create', [
            'templates' => Template::where('is_active', true)->orderBy('kind')->orderBy('name')->get(),
            'selectedTemplateId' => $selectedTemplateId,
            'staff' => $user->school_id
                ? User::where('school_id', $user->school_id)
                    ->where('status', User::STATUS_ACTIVE)
                    ->with('role')
                    ->orderBy('first_name')
                    ->get()
                : collect(),
        ]);
    }

    public function store(StoreTemplateAssignmentRequest $request, TemplateService $service): RedirectResponse
    {
        $data = $request->validated();
        $template = Template::findOrFail($data['template_id']);

        $assignment = $service->createAssignment($request->user(), $template, $data);

        return redirect()
            ->route('assignments.show', $assignment)
            ->with('status', 'Assignment created.');
    }

    public function show(TemplateAssignment $assignment): View
    {
        $this->authorize('view', $assignment);

        return view('templates.assignments.show', [
            'assignment' => $assignment->load(['template', 'creator.role', 'assignee.role', 'school']),
        ]);
    }

    public function markReady(Request $request, TemplateAssignment $assignment, TemplateService $service): RedirectResponse
    {
        $this->authorize('markReady', $assignment);
        $service->markReady($request->user(), $assignment);

        return redirect()->route('assignments.show', $assignment)->with('status', 'Assignment marked ready.');
    }

    public function reopen(Request $request, TemplateAssignment $assignment, TemplateService $service): RedirectResponse
    {
        $this->authorize('reopen', $assignment);
        $service->reopen($request->user(), $assignment);

        return redirect()->route('assignments.show', $assignment)->with('status', 'Assignment reopened.');
    }

    public function print(Request $request, TemplateAssignment $assignment, TemplateService $service): View
    {
        $this->authorize('print', $assignment);
        $service->markPrinted($request->user(), $assignment);

        $assignment->loadMissing(['template', 'school', 'creator', 'assignee']);

        return view('templates.assignments.print', [
            'assignment' => $assignment,
            'layoutView' => $assignment->template->viewName(),
        ]);
    }
}
