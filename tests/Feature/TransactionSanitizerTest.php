<?php

declare(strict_types=1);

use App\Models\ReplacementRule;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\TransactionSanitizer;

function sanitizer(): TransactionSanitizer
{
    return new TransactionSanitizer;
}

it('applies the user replacement map with friendly labels', function (): void {
    ReplacementRule::create(['value' => '38-9009-0123456-00', 'label' => 'Joint Savings']);

    expect(sanitizer()->sanitize('Transfer to 38-9009-0123456-00'))
        ->toBe('Transfer to Joint Savings');
});

it('redacts NZ account numbers including the bank-less form', function (): void {
    Setting::put('fallback_mode', TransactionSanitizer::FALLBACK_REDACT);

    expect(sanitizer()->sanitize('Pay 38-9009-0123456-00'))->toContain('[ACCOUNT]');
    expect(sanitizer()->sanitize('Ref 1318-0251752-01'))->toContain('[ACCOUNT]');
});

it('redacts masked cards, emails and NZ phones', function (): void {
    Setting::put('fallback_mode', TransactionSanitizer::FALLBACK_REDACT);
    $s = sanitizer();

    expect($s->sanitize('Card 524651******'))->toContain('[CARD]');
    expect($s->sanitize('Email jane@example.com'))->toContain('[EMAIL]');
    expect($s->sanitize('Call 021 123 4567'))->toContain('[PHONE]');
});

it('redacts personal names by the initials heuristic', function (): void {
    Setting::put('fallback_mode', TransactionSanitizer::FALLBACK_REDACT);

    expect(sanitizer()->sanitize('Smith J R — EFTPOS'))->toContain('[NAME]');
});

it('produces stable pseudonyms in pseudonym mode', function (): void {
    Setting::put('fallback_mode', TransactionSanitizer::FALLBACK_PSEUDONYM);
    $s = sanitizer();

    $a = $s->sanitize('Smith J R paid you');
    $b = (new TransactionSanitizer)->sanitize('Smith J R paid you again');

    expect($a)->toMatch('/Person-[0-9A-F]{4}/');
    // Same person -> same tag across calls.
    $tagA = preg_replace('/.*?(Person-[0-9A-F]{4}).*/s', '$1', $a);
    $tagB = preg_replace('/.*?(Person-[0-9A-F]{4}).*/s', '$1', $b);
    expect($tagA)->toBe($tagB);
});

it('prefers the map over the regex fallback', function (): void {
    ReplacementRule::create(['value' => 'Smith J R', 'label' => 'Landlord']);

    expect(sanitizer()->sanitize('Smith J R — RENT'))->toContain('Landlord')
        ->not->toContain('[NAME]')
        ->not->toContain('Person-');
});

it('never exposes raw_text in the sanitized transaction shape', function (): void {
    $transaction = Transaction::factory()->create([
        'description' => 'COUNTDOWN METRO',
        'raw_text' => 'SECRET 38-9009-0123456-00 jane@example.com',
        'amount' => -45.50,
        'account' => 'Checking',
        'category' => 'Groceries',
        'date' => '2026-05-10',
    ]);

    $shape = sanitizer()->sanitizeTransaction($transaction);

    expect($shape)->toHaveKeys(['id', 'date', 'amount', 'account', 'category', 'description'])
        ->and($shape)->not->toHaveKey('raw_text');
    expect(json_encode($shape))->not->toContain('SECRET')
        ->not->toContain('jane@example.com');
});
