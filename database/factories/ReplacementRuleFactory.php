<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReplacementRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReplacementRule> */
class ReplacementRuleFactory extends Factory
{
    protected $model = ReplacementRule::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'value' => $this->faker->unique()->numerify('##-####-#######-##'),
            'label' => $this->faker->words(2, true),
        ];
    }
}
