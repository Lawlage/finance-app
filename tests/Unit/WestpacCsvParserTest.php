<?php

declare(strict_types=1);

use App\Services\WestpacCsvParser;

beforeEach(function (): void {
    $this->parser = new WestpacCsvParser;
});

it('parses a valid westpac csv with multiple rows', function (): void {
    $csv = <<<'CSV'
Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code
13/05/2026,2764.35,"Global Digital Solut","Salary",,"Salary",
13/05/2026,-11.00,"Superstar Bakery","EFTPOS TRANSACTION","13-10:32-514","524651******","7473 00514"
CSV;

    $result = $this->parser->parse($csv, 'Checking');

    expect($result)->toHaveCount(2);

    expect($result[0])->toBe([
        'date' => '2026-05-13',
        'description' => 'Global Digital Solut — Salary — Salary',
        'amount' => '2764.35',
        'account' => 'Checking',
        'raw_text' => 'Global Digital Solut | Salary | Salary',
    ]);

    expect($result[1])->toBe([
        'date' => '2026-05-13',
        'description' => 'Superstar Bakery — EFTPOS TRANSACTION — 524651******',
        'amount' => '-11.00',
        'account' => 'Checking',
        'raw_text' => 'Superstar Bakery | EFTPOS TRANSACTION | 13-10:32-514 | 524651****** | 7473 00514',
    ]);
});

it('skips blank lines in csv', function (): void {
    $csv = "Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code\n"
        ."10/05/2026,240.00,\"FRM 1318\",\"DIRECT CREDIT\",\"ref\",\"part\",\"code\"\n"
        ."\n"
        ."\n";

    $result = $this->parser->parse($csv, 'Savings');

    expect($result)->toHaveCount(1);
    expect($result[0]['date'])->toBe('2026-05-10');
});

it('throws on empty csv', function (): void {
    $this->parser->parse('', 'Checking');
})->throws(InvalidArgumentException::class, 'CSV file is empty.');

it('throws on wrong headers', function (): void {
    $csv = "Date,Amount,Payee\n10/05/2026,100.00,Test";

    $this->parser->parse($csv, 'Checking');
})->throws(InvalidArgumentException::class, 'Unexpected CSV headers');

it('handles rows with empty optional fields', function (): void {
    $csv = "Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code\n"
        ."17/05/2026,273.64,\"Fleming K M\",\"AUTOMATIC PAYMENT\",,\"CityRates J\",\n";

    $result = $this->parser->parse($csv, 'Checking');

    expect($result)->toHaveCount(1);
    expect($result[0]['description'])->toBe('Fleming K M — AUTOMATIC PAYMENT — CityRates J');
    expect($result[0]['raw_text'])->toBe('Fleming K M | AUTOMATIC PAYMENT | CityRates J');
});

it('uses the account parameter for all rows', function (): void {
    $csv = "Date,Amount,Other Party,Description,Reference,Particulars,Analysis Code\n"
        ."10/05/2026,240.00,\"Test Party\",\"CREDIT\",\"ref\",\"part\",\"code\"\n"
        ."11/05/2026,-50.00,\"Other Party\",\"DEBIT\",\"ref2\",\"part2\",\"code2\"\n";

    $result = $this->parser->parse($csv, 'Credit Card');

    expect($result[0]['account'])->toBe('Credit Card');
    expect($result[1]['account'])->toBe('Credit Card');
});
