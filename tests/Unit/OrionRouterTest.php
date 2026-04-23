<?php

namespace Tests\Unit;

use App\Services\Orion\OrionRouter;
use App\Services\Orion\TaskType;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrionRouterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);
    }

    public function test_primary_provider_for_report_is_claude(): void
    {
        $router = app(OrionRouter::class);
        $this->assertSame('claude', $router->primaryProviderFor(TaskType::REPORT));
    }

    public function test_primary_provider_for_explanation_is_openai(): void
    {
        $router = app(OrionRouter::class);
        $this->assertSame('openai', $router->primaryProviderFor(TaskType::EXPLANATION));
    }

    public function test_primary_provider_for_data_analysis_is_gemini(): void
    {
        $router = app(OrionRouter::class);
        $this->assertSame('gemini', $router->primaryProviderFor(TaskType::DATA_ANALYSIS));
    }

    public function test_router_throws_when_no_providers_configured(): void
    {
        // None of the env keys set by default => all providers return isConfigured() false.
        $router = app(OrionRouter::class);

        $this->expectException(\RuntimeException::class);
        $router->route('Hello', TaskType::GENERIC);
    }
}
