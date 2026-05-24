<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalysisRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalysisRun> */
class AnalysisRunFactory extends Factory
{
    protected $model = AnalysisRun::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = $this->faker->date();

        return [
            'period_start' => $start,
            'period_end' => $this->faker->dateTimeBetween($start, '+1 month')->format('Y-m-d'),
            'prompt_used' => $this->faker->sentence(),
            'llm_response' => $this->faker->paragraphs(3, true),
            'model' => 'llama3.3:70b-instruct-q4_K_M',
        ];
    }
}
