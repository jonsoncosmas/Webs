<?php

namespace App\Services\Packages;

use App\Models\Package;
use App\Models\School;
use App\Models\User;
use App\Services\ActivityLogger;

class PackageService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function assign(User $actor, School $school, Package $package): School
    {
        $previous = $school->package_id;
        $school->package_id = $package->id;
        $school->save();

        $this->logger->log('package.assigned', $school, [
            'school_id' => $school->id,
            'previous_package_id' => $previous,
            'new_package_id' => $package->id,
            'package_slug' => $package->slug,
        ]);

        return $school->fresh('package');
    }
}
