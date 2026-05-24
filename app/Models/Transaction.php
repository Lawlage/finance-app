<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

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
}
