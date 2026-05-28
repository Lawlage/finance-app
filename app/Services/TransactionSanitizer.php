<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReplacementRule;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Strips personally identifiable information from transaction text before it
 * is exposed to an external Claude client over MCP.
 *
 * Two passes, in order:
 *   1. User-defined replacement map — literal account numbers / names are
 *      swapped for friendly labels ("Joint Savings"). Highest priority, so the
 *      user always controls how known entities appear.
 *   2. Regex fallback — anything sensitive not covered by the map is replaced.
 *      The replacement style is a user setting (`fallback_mode`):
 *        - 'pseudonym' (default): a stable HMAC tag (Person-2B, Account-7F3A) so
 *          recurrence survives for analysis without leaking identity.
 *        - 'redact': flat tokens ([NAME], [ACCOUNT], ...) for maximum privacy.
 */
class TransactionSanitizer
{
    public const FALLBACK_PSEUDONYM = 'pseudonym';

    public const FALLBACK_REDACT = 'redact';

    /**
     * Detection patterns: [type prefix, flat token, regex].
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const array PATTERNS = [
        // NZ bank account, e.g. 38-9009-0123456-00 or the bank-less
        // branch-account-suffix form 1318-0251752-01.
        ['Account', '[ACCOUNT]', '/\b(?:\d{2}-)?\d{4}-\d{7}-\d{2,3}\b/'],
        // Masked card / account numbers, e.g. 524651****** (no trailing \b: '*' is
        // not a word char, so a boundary never follows the asterisks).
        ['Card', '[CARD]', '/\b\d{2,}\*{2,}\d*/'],
        // Email addresses
        ['Email', '[EMAIL]', '/\b[\w.+-]+@[\w-]+\.[\w.-]+\b/'],
        // NZ phone numbers, e.g. 021 123 4567, +64 21 123 4567
        ['Phone', '[PHONE]', '/(?:\+?64|0)[-\s]?\d{1,2}[-\s]?\d{3}[-\s]?\d{3,4}\b/'],
        // Personal-name heuristic: "Surname X Y" initials, e.g. Fleming K M
        ['Person', '[NAME]', '/\b[A-Z][a-z]+\s+[A-Z](?:\s+[A-Z])+\b/'],
    ];

    /** @var Collection<int, ReplacementRule>|null */
    private ?Collection $rules = null;

    private ?string $fallbackMode = null;

    public function sanitize(string $text): string
    {
        $text = $this->applyMap($text);

        return $this->applyFallback($text);
    }

    /**
     * The MCP-safe representation of a transaction. Only these fields ever leave
     * the box — never `raw_text`.
     *
     * @return array{id: int, date: string, amount: string, account: string, category: string|null, description: string}
     */
    public function sanitizeTransaction(Transaction $transaction): array
    {
        return [
            'id' => (int) $transaction->id,
            'date' => Carbon::parse($transaction->date)->format('Y-m-d'),
            'amount' => (string) $transaction->amount,
            'account' => (string) $transaction->account,
            'category' => $transaction->category,
            'description' => $this->sanitize((string) $transaction->description),
        ];
    }

    private function applyMap(string $text): string
    {
        foreach ($this->rules() as $rule) {
            $text = str_ireplace((string) $rule->value, (string) $rule->label, $text);
        }

        return $text;
    }

    private function applyFallback(string $text): string
    {
        $pseudonymise = $this->fallbackMode() === self::FALLBACK_PSEUDONYM;

        foreach (self::PATTERNS as [$prefix, $token, $pattern]) {
            $text = (string) preg_replace_callback(
                $pattern,
                fn (array $m): string => $pseudonymise ? $this->pseudonym($prefix, $m[0]) : $token,
                $text,
            );
        }

        return $text;
    }

    /**
     * Deterministic, non-reversible tag for a matched value. The same value
     * always maps to the same tag, so Claude can recognise recurring entities
     * without ever seeing the real number or name.
     */
    private function pseudonym(string $prefix, string $value): string
    {
        $key = config('app.key');
        $hash = hash_hmac('sha256', mb_strtolower(trim($value)), is_string($key) ? $key : '');

        return $prefix.'-'.mb_strtoupper(mb_substr($hash, 0, 4));
    }

    /** @return Collection<int, ReplacementRule> */
    private function rules(): Collection
    {
        if (! $this->rules instanceof Collection) {
            // Longest values first so specific matches win over partial overlaps.
            $this->rules = ReplacementRule::all()
                ->sortByDesc(fn (ReplacementRule $rule): int => mb_strlen((string) $rule->value))
                ->values();
        }

        return $this->rules;
    }

    private function fallbackMode(): string
    {
        if ($this->fallbackMode === null) {
            $mode = Setting::get('fallback_mode', self::FALLBACK_PSEUDONYM);
            $this->fallbackMode = $mode === self::FALLBACK_REDACT
                ? self::FALLBACK_REDACT
                : self::FALLBACK_PSEUDONYM;
        }

        return $this->fallbackMode;
    }
}
