<?php

namespace Database\Factories;

use App\Models\Prize;
use App\Models\PrizeDraw;
use App\Models\RaffleEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrizeDraw>
 */
class PrizeDrawFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prize_id' => Prize::factory(),
            'raffle_entry_id' => RaffleEntry::factory(),
        ];
    }

    /**
     * Indicate that the winner forfeited the prize and it was redrawn.
     */
    public function forfeited(): static
    {
        return $this->state(fn (array $attributes) => [
            'forfeited_at' => now(),
        ]);
    }
}
