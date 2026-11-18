<?php

namespace Database\Seeders;

use App\Models\RaffleEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RaffleEntrySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed sample raffle entries for local development.
     */
    public function run(): void
    {
        RaffleEntry::factory(25)->create();
    }
}
