<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnalysisRun;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Import;
use App\Models\ReplacementRule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates a realistic, self-contained demo dataset: ~6 months of NZ personal
 * finance activity across an everyday account, a savings account, and a
 * mortgage (loan) account, plus categories, auto-categorisation rules, PII
 * replacement rules, import history, and a sample MCP spending analysis.
 *
 * Run with: php artisan db:seed --class=DemoSeeder
 * Idempotent — it clears the demo tables first, so it is safe to re-run.
 */
class DemoSeeder extends Seeder
{
    private const string EVERYDAY = 'Westpac Choice';

    private const string SAVINGS = 'Westpac Savings';

    private const string MORTGAGE = 'Mortgage 1';

    /** @var array<int, array{date: string, description: string, amount: string, category: string|null, category_locked: bool, account: string, raw_text: string, import_id: int|null}> */
    private array $rows = [];

    public function run(): void
    {
        $this->fresh();
        $this->seedDemoUser();
        $this->seedCategories();
        $this->seedCategoryRules();
        $this->seedReplacementRules();

        [$everydayImport, $savingsImport] = $this->seedImports();

        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        for ($month = 0; $month < 6; $month++) {
            $this->seedMonth($start->addMonths($month), $everydayImport->id, $savingsImport->id);
        }

        $this->persistTransactions();
        $this->applyCategoryRules();
        $this->refreshImportCounts();
        $this->seedAnalysis($start);

        $this->command->info(sprintf('Seeded %d demo transactions.', Transaction::query()->count()));
    }

    /**
     * Clear demo-related tables so the seeder can be re-run cleanly.
     */
    private function fresh(): void
    {
        Transaction::query()->delete();
        Import::query()->delete();
        CategoryRule::query()->delete();
        Category::query()->delete();
        AnalysisRun::query()->delete();
        ReplacementRule::query()->delete();
    }

    private function seedDemoUser(): void
    {
        if (User::query()->where('email', 'demo@example.com')->exists()) {
            return;
        }

        // Login: demo@example.com / password
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);
    }

    private function seedCategories(): void
    {
        $names = [
            'Income', 'Rent', 'Groceries', 'Dining', 'Transport', 'Utilities',
            'Subscriptions', 'Healthcare', 'Entertainment', 'Shopping',
            Transaction::TRANSFER_CATEGORY, 'Other',
        ];

        foreach ($names as $name) {
            Category::query()->create(['name' => $name]);
        }
    }

    private function seedCategoryRules(): void
    {
        /** @var array<int, array{string, string}> $rules */
        $rules = [
            ['Countdown', 'Groceries'],
            ['New World', 'Groceries'],
            ["PAK'nSAVE", 'Groceries'],
            ['Z Energy', 'Transport'],
            ['BP Connect', 'Transport'],
            ['AT HOP', 'Transport'],
            ['Contact Energy', 'Utilities'],
            ['Spark', 'Utilities'],
            ['Netflix', 'Subscriptions'],
            ['Spotify', 'Subscriptions'],
            ['Mitre 10', 'Shopping'],
            ['Salary', 'Income'],
        ];

        foreach ($rules as [$pattern, $category]) {
            CategoryRule::query()->create([
                'pattern' => $pattern,
                'category' => $category,
            ]);
        }
    }

    private function seedReplacementRules(): void
    {
        ReplacementRule::query()->create([
            'value' => '38-9009-0123456-00',
            'label' => 'Joint Savings',
        ]);
        ReplacementRule::query()->create([
            'value' => 'Jordan Whetu',
            'label' => 'Account holder',
        ]);
    }

    /**
     * @return array{0: Import, 1: Import}
     */
    private function seedImports(): array
    {
        $everyday = Import::query()->create([
            'filename' => 'westpac-choice-statement.csv',
            'account' => self::EVERYDAY,
            'transaction_count' => 0,
        ]);
        $savings = Import::query()->create([
            'filename' => 'westpac-savings-statement.csv',
            'account' => self::SAVINGS,
            'transaction_count' => 0,
        ]);

        return [$everyday, $savings];
    }

    private function seedMonth(CarbonImmutable $month, int $everydayImportId, int $savingsImportId): void
    {
        // Fortnightly salary (income).
        $this->add($month->addDays(0), 'Salary — Acme Robotics Ltd', 2480.00, 'Income', self::EVERYDAY, $everydayImportId, true);
        $this->add($month->addDays(14), 'Salary — Acme Robotics Ltd', 2480.00, 'Income', self::EVERYDAY, $everydayImportId, true);

        // Rent (manually categorised + locked).
        $this->add($month->addDays(1), 'Rent payment — Barfoot & Thompson', -2200.00, 'Rent', self::EVERYDAY, $everydayImportId, true);

        // Weekly groceries — rotate supermarkets so rules pick them up.
        $supermarkets = ['Countdown Ponsonby', 'New World Metro', "PAK'nSAVE Mt Albert", 'Countdown Grey Lynn'];
        for ($week = 0; $week < 4; $week++) {
            $store = $supermarkets[$week % count($supermarkets)];
            $this->add($month->addDays(3 + $week * 7), $store, -$this->money(85, 190), null, self::EVERYDAY, $everydayImportId);
        }

        // Fuel + public transport.
        $this->add($month->addDays(6), 'Z Energy Kingsland', -$this->money(70, 110), null, self::EVERYDAY, $everydayImportId);
        $this->add($month->addDays(20), 'BP Connect Newmarket', -$this->money(65, 105), null, self::EVERYDAY, $everydayImportId);
        $this->add($month->addDays(2), 'AT HOP top-up', -40.00, null, self::EVERYDAY, $everydayImportId);

        // Dining out.
        $eateries = ['Best Ugly Bagels', 'Hello Beasty', 'Chamberlain Coffee', 'Bestie Cafe', 'Gemmayze St'];
        for ($i = 0; $i < 3; $i++) {
            $venue = $eateries[($month->month + $i) % count($eateries)];
            $this->add($month->addDays(5 + $i * 9), $venue, -$this->money(18, 95), 'Dining', self::EVERYDAY, $everydayImportId);
        }

        // Utilities — power, internet, mobile.
        $this->add($month->addDays(10), 'Contact Energy', -$this->money(140, 230), null, self::EVERYDAY, $everydayImportId);
        $this->add($month->addDays(12), 'Spark Broadband', -89.99, null, self::EVERYDAY, $everydayImportId);
        $this->add($month->addDays(12), 'Spark Mobile', -45.00, null, self::EVERYDAY, $everydayImportId);

        // Subscriptions.
        $this->add($month->addDays(8), 'Netflix.com', -23.99, null, self::EVERYDAY, $everydayImportId);
        $this->add($month->addDays(15), 'Spotify Premium', -16.99, null, self::EVERYDAY, $everydayImportId);

        // Occasional shopping + healthcare.
        $this->add($month->addDays(18), 'Mitre 10 Mega', -$this->money(25, 180), null, self::EVERYDAY, $everydayImportId);
        if ($month->month % 2 === 0) {
            $this->add($month->addDays(22), 'Unichem Pharmacy', -$this->money(15, 70), 'Healthcare', self::EVERYDAY, $everydayImportId);
        }

        // A genuinely uncategorised line for the demo (no rule, no manual set).
        $this->add($month->addDays(24), 'POS W/D Eftpos 1042', -$this->money(20, 60), null, self::EVERYDAY, $everydayImportId);

        // Transfer to savings — excluded from spending/income aggregations.
        $this->add($month->addDays(16), 'Transfer to 38-9009-0123456-00', -600.00, Transaction::TRANSFER_CATEGORY, self::EVERYDAY, $everydayImportId, true);
        $this->add($month->addDays(16), 'Transfer from Westpac Choice', 600.00, Transaction::TRANSFER_CATEGORY, self::SAVINGS, $savingsImportId, true);

        // Mortgage account: repayment (transfer out of cash) + interest charge.
        // These live on a loan account and are excluded from income/expense.
        $this->add($month->addDays(2), 'Mortgage repayment', -1850.00, Transaction::TRANSFER_CATEGORY, self::EVERYDAY, $everydayImportId, true);
        $this->add($month->addDays(2), 'Loan repayment received', 1850.00, Transaction::TRANSFER_CATEGORY, self::MORTGAGE, null, true);
        $this->add($month->addDays(28), 'Interest charged', -1120.00, 'Other', self::MORTGAGE, null, true);
    }

    private function add(
        CarbonImmutable $date,
        string $description,
        float $amount,
        ?string $category,
        string $account,
        ?int $importId,
        bool $locked = false,
    ): void {
        $this->rows[] = [
            'date' => $date->toDateString(),
            'description' => $description,
            'amount' => number_format($amount, 2, '.', ''),
            'category' => $category,
            'category_locked' => $locked,
            'account' => $account,
            'raw_text' => mb_strtoupper($description).' '.$date->format('dMy'),
            'import_id' => $importId,
        ];
    }

    /**
     * Realistic-looking spend within a range, to 2 decimals.
     */
    private function money(int $min, int $max): float
    {
        return random_int($min * 100, $max * 100) / 100;
    }

    private function persistTransactions(): void
    {
        $now = CarbonImmutable::now()->toDateTimeString();

        $payload = array_map(
            static fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
            $this->rows,
        );

        foreach (array_chunk($payload, 100) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }
    }

    /**
     * Apply the seeded keyword rules to uncategorised, unlocked transactions —
     * the same substring match the CategorizeTransactions job uses. Lines with
     * no matching rule (e.g. raw EFTPOS withdrawals) stay uncategorised, ready
     * to be categorised manually or by the Claude client over MCP.
     */
    private function applyCategoryRules(): void
    {
        $rules = CategoryRule::query()->get(['pattern', 'category']);

        Transaction::query()
            ->whereNull('category')
            ->where('category_locked', false)
            ->each(function (Transaction $transaction) use ($rules): void {
                foreach ($rules as $rule) {
                    if (str_contains(mb_strtolower($transaction->description), mb_strtolower($rule->pattern))) {
                        $transaction->update(['category' => $rule->category]);

                        return;
                    }
                }
            });
    }

    private function refreshImportCounts(): void
    {
        Import::query()->each(function (Import $import): void {
            $import->update([
                'transaction_count' => Transaction::query()
                    ->where('import_id', $import->id)
                    ->count(),
            ]);
        });
    }

    private function seedAnalysis(CarbonImmutable $start): void
    {
        $periodStart = $start;
        $periodEnd = CarbonImmutable::now()->endOfMonth();

        $response = <<<'MARKDOWN'
            ## Spending overview

            Across the period your **largest discretionary categories** were Groceries and Dining.
            Fixed costs (Rent, Utilities, Subscriptions) were stable month to month.

            ### What stood out
            - **Groceries** averaged ~$520/month across four supermarket trips — consistent, no spikes.
            - **Dining** ranged $50–$220/month; the months with three+ café visits drove the high end.
            - **Subscriptions** total ~$41/month (Netflix + Spotify). Low, but easy to trim if needed.

            ### Recommendations
            1. You're reliably transferring **$600/month to savings** — consider nudging this to $750 in higher-income months.
            2. Fuel + transport sits around **$200/month**; an AT HOP monthly pass may be cheaper than top-ups.
            3. Nothing alarming here — your income comfortably covers fixed costs and savings.
            MARKDOWN;

        AnalysisRun::query()->create([
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'prompt_used' => null,
            'llm_response' => $response,
            'model' => 'claude (mcp)',
        ]);
    }
}
