<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Http\RedirectResponse;

class ImportController extends Controller
{
    public function destroy(Import $import): RedirectResponse
    {
        $import->transactions()->delete();
        $import->delete();

        return redirect()->back()->with('success', 'Import and its transactions deleted.');
    }
}
