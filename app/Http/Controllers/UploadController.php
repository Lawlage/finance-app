<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadStatementRequest;
use App\Jobs\ProcessUploadedStatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Upload');
    }

    public function store(UploadStatementRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('statement');
        $path = $file->store('uploads', 'local');

        if ($path === false) {
            return redirect()->back()->withErrors(['statement' => 'Failed to store the uploaded file.']);
        }

        ProcessUploadedStatement::dispatch($path, $request->string('account')->toString());

        return redirect()->back()->with('success', 'Statement uploaded. Processing has been queued.');
    }
}
