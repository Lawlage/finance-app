<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RunAnalysisRequest;
use App\Jobs\RunSpendingAnalysis;
use App\Models\AnalysisRun;
use App\Models\JobStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnalysisController extends Controller
{
    public function index(): Response
    {
        $analyses = AnalysisRun::orderByDesc('created_at')->get();

        return Inertia::render('Analysis', [
            'analyses' => $analyses,
        ]);
    }

    public function store(RunAnalysisRequest $request): RedirectResponse
    {
        $status = JobStatus::start('analysis', 'Running spending analysis...');
        RunSpendingAnalysis::dispatch(
            $request->string('period_start')->toString(),
            $request->string('period_end')->toString(),
            $status->id,
        );

        return redirect()->back()->with('success', 'Analysis job dispatched. Results will appear shortly.');
    }

    public function show(AnalysisRun $analysisRun): Response
    {
        return Inertia::render('AnalysisShow', [
            'analysis' => $analysisRun,
        ]);
    }

    public function destroy(AnalysisRun $analysisRun): RedirectResponse
    {
        $analysisRun->delete();

        return redirect()->route('analysis')->with('success', 'Analysis deleted.');
    }
}
