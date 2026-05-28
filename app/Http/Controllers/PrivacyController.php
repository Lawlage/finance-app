<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\McpAccessLog;
use App\Models\ReplacementRule;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\TransactionSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function index(): Response
    {
        $rules = ReplacementRule::orderBy('label')->get()
            ->map(fn (ReplacementRule $rule): array => [
                'id' => $rule->id,
                'value' => $rule->value,
                'label' => $rule->label,
            ]);

        return Inertia::render('Privacy', [
            'rules' => $rules,
            'fallbackMode' => Setting::get('fallback_mode', TransactionSanitizer::FALLBACK_PSEUDONYM),
            'accountLabels' => Transaction::query()->distinct()->orderBy('account')->pluck('account'),
            'auditLog' => McpAccessLog::query()
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(['id', 'primitive', 'name', 'payload', 'created_at']),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fallback_mode' => ['required', Rule::in([
                TransactionSanitizer::FALLBACK_PSEUDONYM,
                TransactionSanitizer::FALLBACK_REDACT,
            ])],
        ]);

        Setting::put('fallback_mode', $validated['fallback_mode']);

        return redirect()->back()->with('success', 'Privacy settings updated.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        ReplacementRule::create($validated);

        return redirect()->back()->with('success', 'Replacement added.');
    }

    public function updateRule(Request $request, ReplacementRule $replacementRule): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $replacementRule->update($validated);

        return redirect()->back()->with('success', 'Replacement updated.');
    }

    public function destroyRule(ReplacementRule $replacementRule): RedirectResponse
    {
        $replacementRule->delete();

        return redirect()->back()->with('success', 'Replacement deleted.');
    }
}
