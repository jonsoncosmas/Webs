<?php

namespace App\Http\Controllers\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\School;
use App\Services\Packages\PackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(private readonly PackageService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Package::class);

        return view('packages.index', [
            'packages' => Package::query()->orderBy('id')->get(),
            'schools' => School::query()->with('package')->orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request, School $school): RedirectResponse
    {
        $this->authorize('assignToSchool', [Package::class, $school]);

        $data = $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
        ]);

        $package = Package::findOrFail($data['package_id']);
        $this->service->assign($request->user(), $school, $package);

        return redirect()
            ->route('packages.index')
            ->with('status', "Assigned {$package->name} to {$school->name}.");
    }

    public function school(Request $request): View
    {
        $school = $request->user()->school;
        if (! $school) {
            abort(404);
        }
        $this->authorize('viewSchoolSubscription', [Package::class, $school]);

        return view('packages.school', [
            'school' => $school->loadMissing('package'),
        ]);
    }
}
