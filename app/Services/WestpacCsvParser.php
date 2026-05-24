<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class WestpacCsvParser
{
    private const array EXPECTED_HEADERS = [
        'Date',
        'Amount',
        'Other Party',
        'Description',
        'Reference',
        'Particulars',
        'Analysis Code',
    ];

    /**
     * Parse a Westpac NZ CSV file and return normalised transaction rows.
     *
     * @return array<int, array{date: string, description: string, amount: string, account: string, raw_text: string}>
     */
    public function parse(string $csvContent, string $account): array
    {
        $trimmed = trim($csvContent);

        if ($trimmed === '') {
            throw new InvalidArgumentException('CSV file is empty.');
        }

        $lines = explode("\n", $trimmed);

        $headerLine = array_shift($lines);
        /** @var array<int, string> $headers */
        $headers = str_getcsv($headerLine);
        $headers = array_map(trim(...), $headers);

        if ($headers !== self::EXPECTED_HEADERS) {
            throw new InvalidArgumentException(
                'Unexpected CSV headers. Expected Westpac NZ format: '.implode(', ', self::EXPECTED_HEADERS),
            );
        }

        $transactions = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            /** @var array<int, string|null> $fields */
            $fields = str_getcsv($line);

            if (count($fields) < 7) {
                continue;
            }

            $date = Carbon::createFromFormat('d/m/Y', trim((string) $fields[0]));

            if (! $date instanceof Carbon) {
                throw new InvalidArgumentException("Invalid date format: {$fields[0]}");
            }

            $otherParty = trim((string) ($fields[2] ?? ''));
            $description = trim((string) ($fields[3] ?? ''));
            $reference = trim((string) ($fields[4] ?? ''));
            $particulars = trim((string) ($fields[5] ?? ''));
            $analysisCode = trim((string) ($fields[6] ?? ''));

            $descriptionParts = array_filter([$otherParty, $description, $particulars], fn (string $s): bool => $s !== '');

            $rawParts = array_filter(
                [$otherParty, $description, $reference, $particulars, $analysisCode],
                fn (string $s): bool => $s !== '',
            );

            $transactions[] = [
                'date' => $date->format('Y-m-d'),
                'description' => implode(' — ', $descriptionParts),
                'amount' => trim((string) $fields[1]),
                'account' => $account,
                'raw_text' => implode(' | ', $rawParts),
            ];
        }

        return $transactions;
    }
}
