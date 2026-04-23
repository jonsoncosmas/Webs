<?php

namespace App\Http\Controllers\Orion;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\ActivityLogger;
use App\Services\Orion\OrionRouter;
use App\Services\Orion\TaskType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrionController extends Controller
{
    public function ask(Request $request, OrionRouter $orion, ActivityLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:1', 'max:4000'],
            'task_type' => ['nullable', 'in:report,explanation,data_analysis,generic'],
        ]);

        $taskType = $data['task_type'] ?? TaskType::GENERIC;
        $user = $request->user();

        try {
            $result = $orion->route($data['prompt'], $taskType, [
                'user_id' => $user->id,
                'school_id' => $user->school_id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'brand' => config('orion.brand'),
                'message' => 'ORION AI is currently unavailable. Please try again shortly.',
            ], 503);
        }

        $logger->log('orion.ask', null, ['task_type' => $taskType, 'request_id' => $result['request_id']]);

        $payload = [
            'ok' => true,
            'brand' => config('orion.brand'),
            'content' => $result['content'],
        ];

        // Only System Admin sees which provider ORION used.
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            $payload['provider'] = $result['provider'];
            $payload['model'] = $result['model'];
        }

        return response()->json($payload);
    }
}
