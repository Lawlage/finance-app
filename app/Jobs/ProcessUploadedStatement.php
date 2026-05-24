<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\WestpacCsvParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $filePath,
        private readonly string $account,
    ) {}

    public function handle(WestpacCsvParser $parser): void
    {
        Log::info('Processing uploaded statement', [
            'file' => $this->filePath,
            'account' => $this->account,
        ]);

        $contents = Storage::get($this->filePath);

        if ($contents === null) {
            Log::error('Statement file not found', ['file' => $this->filePath]);
            Storage::delete($this->filePath);

            return;
        }

        $rows = $parser->parse($contents, $this->account);
        $inserted = 0;
        $skipped = 0;
        /** @var array<int, int> $newIds */
        $newIds = [];

        foreach ($rows as $row) {
            $exists = Transaction::where('date', $row['date'])
                ->where('amount', $row['amount'])
                ->where('account', $row['account'])
                ->where('raw_text', $row['raw_text'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $transaction = Transaction::create($row);
            $newIds[] = (int) $transaction->id;
            $inserted++;
        }

        Log::info('Statement processing complete', [
            'file' => $this->filePath,
            'total' => count($rows),
            'inserted' => $inserted,
            'skipped' => $skipped,
        ]);

        if ($newIds !== []) {
            $uncategorized = Transaction::whereIn('id', $newIds)
                ->whereNull('category')
                ->pluck('id')
                ->toArray();

            /** @var array<int, int> $uncategorized */
            if ($uncategorized !== []) {
                CategorizeTransactions::dispatch($uncategorized);
            }
        }

        Storage::delete($this->filePath);
    }
}
