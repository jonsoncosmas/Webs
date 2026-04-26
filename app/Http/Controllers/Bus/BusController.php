<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\BusRoute;
use App\Models\BusVehicle;
use App\Models\Role;
use App\Models\School;
use App\Services\Bus\BusTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class BusController extends Controller
{
    public function __construct(private readonly BusTrackingService $service) {}

    public function index(Request $request): View
    {
        $school = $this->school($request);
        $this->authorize('manageSchool', [BusRoute::class, $school]);

        return view('bus.index', [
            'school' => $school,
            'routes' => BusRoute::query()
                ->where('school_id', $school->id)
                ->with('stops', 'vehicles')
                ->orderBy('name')
                ->get(),
            'vehicles' => BusVehicle::query()
                ->where('school_id', $school->id)
                ->with('route', 'driver')
                ->orderBy('plate_number')
                ->get(),
        ]);
    }

    public function map(Request $request): View
    {
        $school = $this->school($request);
        $this->authorize('manageSchool', [BusRoute::class, $school]);

        return view('bus.map', [
            'school' => $school,
            'vehicles' => BusVehicle::query()
                ->where('school_id', $school->id)
                ->where('status', BusVehicle::STATUS_ACTIVE)
                ->with('route')
                ->get(),
            'apiKey' => config('services.google_maps.key'),
        ]);
    }

    public function storeRoute(Request $request): RedirectResponse
    {
        $school = $this->school($request);
        $this->authorize('manageSchool', [BusRoute::class, $school]);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('bus_routes', 'name')->where('school_id', $school->id),
            ],
            'code' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->createRoute($request->user(), $school, $data);

        return back()->with('status', "Route '{$data['name']}' created.");
    }

    public function storeStop(Request $request, BusRoute $route): RedirectResponse
    {
        $this->authorize('updateRoute', $route);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'scheduled_pickup' => ['nullable', 'date_format:H:i'],
            'scheduled_dropoff' => ['nullable', 'date_format:H:i'],
        ]);

        $this->service->addStop($request->user(), $route, $data);

        return back()->with('status', "Stop '{$data['name']}' added to {$route->name}.");
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $school = $this->school($request);
        $this->authorize('manageSchool', [BusRoute::class, $school]);

        $data = $request->validate([
            'plate_number' => [
                'required', 'string', 'max:32',
                Rule::unique('bus_vehicles', 'plate_number')->where('school_id', $school->id),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:200'],
            'bus_route_id' => [
                'nullable', 'integer',
                Rule::exists('bus_routes', 'id')->where('school_id', $school->id),
            ],
            'driver_user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
        ]);

        try {
            $this->service->createVehicle($request->user(), $school, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['plate_number' => $e->getMessage()])->withInput();
        }

        return back()->with('status', "Vehicle '{$data['plate_number']}' added.");
    }

    public function recordPosition(Request $request, BusVehicle $vehicle): RedirectResponse
    {
        $this->authorize('updateVehicle', $vehicle);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $this->service->recordPosition($request->user(), $vehicle, (float) $data['latitude'], (float) $data['longitude']);

        return back()->with('status', "Position updated for {$vehicle->plate_number}.");
    }

    private function school(Request $request): School
    {
        $actor = $request->user();
        if ($actor->hasRole(Role::SYSTEM_ADMIN) && $request->filled('school_id')) {
            return School::findOrFail($request->integer('school_id'));
        }
        $school = $actor->school;
        if (! $school) {
            abort(404, 'No school context available.');
        }

        return $school;
    }
}
