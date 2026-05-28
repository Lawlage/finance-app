<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Transactions in this category are inter-account transfers — money that
     * moved but did not enter or leave your finances — so they are excluded
     * from income/expense and spending aggregations.
     */
    public const TRANSFER_CATEGORY = 'Transfer';

    /**
     * Loan (non-cash) account labels. Their ledger lines mirror cash-account
     * flows: a loan repayment debits a cash account and credits the loan, and
     * interest is charged against the loan. Counting these alongside the cash
     * side double-counts the money, so they are excluded from aggregations.
     *
     * @var list<string>
     */
    public const LOAN_ACCOUNTS = ['Mortgage 1', 'Mortgage 2', 'Mortgage 3'];

    protected $fillable = [
        'date',
        'description',
        'amount',
        'category',
        'category_locked',
        'account',
        'raw_text',
        'import_id',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'category_locked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Exclude inter-account transfers while keeping uncategorized transactions.
     *
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function excludingTransfers(Builder $query): void
    {
        $query->where(function (Builder $inner): void {
            $inner->whereNull('category')
                ->orWhere('category', '!=', self::TRANSFER_CATEGORY);
        });
    }

    /**
     * Exclude loan-account ledger lines, which mirror cash-account flows and
     * would otherwise double-count money in income/expense aggregations.
     *
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function excludingLoanAccounts(Builder $query): void
    {
        $query->whereNotIn('account', self::LOAN_ACCOUNTS);
    }
}
