<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\JobStatus;
use Illuminate\Http\JsonResponse;

class JobStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $statuses = JobStatus::where('created_at', '>=', now()->subHour())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($statuses);
    }

    public function dismiss(JobStatus $jobStatus): JsonResponse
    {
        $jobStatus->delete();

        return response()->json(['ok' => true]);
    }
}
