<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
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
