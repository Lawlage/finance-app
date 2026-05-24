<?php

declare(strict_types=1);

use App\Jobs\CategorizeTransactions;
use App\Jobs\ProcessUploadedStatement;
use App\Models\Transaction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Queue::fake([CategorizeTransactions::class]);
});

it('parses a westpac csv and creates transactions', function (): void {
    $csv = <<<'CSV'
Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code
05/13/2026,2764.35,"Global Digital Solut","Salary",,"Salary",
05/13/2026,-11.00,"Superstar Bakery","EFTPOS TRANSACTION","13-10:32-514","524651******","7473 00514"
CSV;

    Storage::disk('local')->put('uploads/test.csv', $csv);

    ProcessUploadedStatement::dispatchSync('uploads/test.csv', 'Checking');

    expect(Transaction::count())->toBe(2);

    $salary = Transaction::where('amount', '2764.35')->first();
    expect($salary)->not->toBeNull();
    expect($salary->date->format('Y-m-d'))->toBe('2026-05-13');
    expect($salary->account)->toBe('Checking');
    expect($salary->description)->toContain('Global Digital Solut');

    $eftpos = Transaction::where('amount', '-11.00')->first();
    expect($eftpos)->not->toBeNull();
    expect($eftpos->description)->toContain('Superstar Bakery');
});

it('dispatches categorize job for uncategorized transactions', function (): void {
    $csv = <<<'CSV'
Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code
05/13/2026,-11.00,"Superstar Bakery","EFTPOS TRANSACTION","13-10:32-514","524651******","7473 00514"
CSV;

    Storage::disk('local')->put('uploads/test.csv', $csv);

    ProcessUploadedStatement::dispatchSync('uploads/test.csv', 'Checking');

    Queue::assertPushed(CategorizeTransactions::class);
});

it('skips duplicate transactions on re-upload', function (): void {
    $csv = <<<'CSV'
Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code
05/13/2026,2764.35,"Global Digital Solut","Salary",,"Salary",
CSV;

    Storage::disk('local')->put('uploads/first.csv', $csv);
    ProcessUploadedStatement::dispatchSync('uploads/first.csv', 'Checking');
    expect(Transaction::count())->toBe(1);

    Storage::disk('local')->put('uploads/second.csv', $csv);
    ProcessUploadedStatement::dispatchSync('uploads/second.csv', 'Checking');
    expect(Transaction::count())->toBe(1);
});

it('deletes the uploaded file after processing', function (): void {
    $csv = <<<'CSV'
Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code
05/13/2026,2764.35,"Global Digital Solut","Salary",,"Salary",
CSV;

    Storage::disk('local')->put('uploads/test.csv', $csv);

    ProcessUploadedStatement::dispatchSync('uploads/test.csv', 'Checking');

    Storage::disk('local')->assertMissing('uploads/test.csv');
});

it('handles missing file gracefully', function (): void {
    ProcessUploadedStatement::dispatchSync('uploads/nonexistent.csv', 'Checking');

    expect(Transaction::count())->toBe(0);
});
