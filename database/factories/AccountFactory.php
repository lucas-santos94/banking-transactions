<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'number' => $this->faker->unique()->numberBetween(100000, 999999),
            'balance' => $this->faker->numberBetween(1000, 100000),
            'credit_limit' => $this->faker->numberBetween(1000, 50000),
        ];
    }
}
