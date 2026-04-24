<?php

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Models\Template;
use App\Services\Templates\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Template::class);

        $user = $request->user();
        $query = Template::query()->latest();
        if (! $user->can('create', Template::class)) {
            // Non-System-Admins see only active templates.
            $query->where('is_active', true);
        }

        return view('templates.index', [
            'templates' => $query->with('creator')->paginate(20),
            'canCreate' => $user->can('create', Template::class),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Template::class);

        return view('templates.create', [
            'kinds' => Template::kinds(),
        ]);
    }

    public function store(StoreTemplateRequest $request, TemplateService $service): RedirectResponse
    {
        $payload = $request->validated();
        $payload['layout'] = [
            'view' => $payload['layout_view'] ?? $this->defaultLayoutFor($payload['kind']),
        ];

        $template = $service->createTemplate($request->user(), $payload);

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'Template created.');
    }

    public function show(Template $template): View
    {
        $this->authorize('view', $template);

        return view('templates.show', [
            'template' => $template->load('creator'),
        ]);
    }

    public function archive(Template $template, TemplateService $service): RedirectResponse
    {
        $this->authorize('archive', $template);

        $service->toggleActive(request()->user(), $template, false);

        return redirect()->route('templates.show', $template)->with('status', 'Template archived.');
    }

    public function activate(Template $template, TemplateService $service): RedirectResponse
    {
        $this->authorize('archive', $template);

        $service->toggleActive(request()->user(), $template, true);

        return redirect()->route('templates.show', $template)->with('status', 'Template activated.');
    }

    private function defaultLayoutFor(string $kind): string
    {
        return match ($kind) {
            Template::KIND_RESULT_MARKLIST => 'templates.layouts.result_marklist_basic',
            Template::KIND_ACADEMIC_REPORT => 'templates.layouts.academic_report_standard',
            default => 'templates.layouts.generic',
        };
    }
}
