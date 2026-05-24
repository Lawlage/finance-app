<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadStatementRequest;
use App\Jobs\ProcessUploadedStatement;
use App\Models\Import;
use App\Models\JobStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function index(): Response
    {
        $imports = Import::orderByDesc('created_at')->get();

        return Inertia::render('Upload', [
            'imports' => $imports,
        ]);
    }

    public function store(UploadStatementRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('statement');
        $path = $file->store('uploads', 'local');

        if ($path === false) {
            return redirect()->back()->withErrors(['statement' => 'Failed to store the uploaded file.']);
        }

        $status = JobStatus::start('import', 'Importing statement...');
        ProcessUploadedStatement::dispatch($path, $request->string('account')->toString(), $status->id);

        return redirect()->back()->with('success', 'Statement uploaded. Processing has been queued.');
    }
}
