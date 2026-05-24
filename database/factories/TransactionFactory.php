<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, -500, 500),
            'category' => $this->faker->optional(0.7)->randomElement([
                'Groceries', 'Dining', 'Transport', 'Utilities',
                'Income', 'Rent', 'Healthcare', 'Entertainment', 'Other',
            ]),
            'account' => $this->faker->randomElement(['Checking', 'Savings', 'Credit Card']),
            'raw_text' => $this->faker->sentence(),
        ];
    }
}
