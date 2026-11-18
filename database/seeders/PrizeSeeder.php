<?php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrizeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The raffle's gift voucher prizes, from the lowest to the grand prize.
     *
     * @var list<string>
     */
    private const PRIZES = [
        'Dinner for two at Cinnamon Grand, Colombo',
        'Dinner for two at Cinnamon Lakeside, Colombo',
        'Dinner for two at Cinnamon Life, Colombo',
        'One-night stay for two at Blackpool Hotel, Nuwara Eliya',
        'One-night stay for two at Amaara Sky Hotel, Kandy',
        'One-night stay for two at Galway Heights, Nuwara Eliya',
        'One-night stay for two at Mermaid Hotel & Club by Jetwing Hotels',
        'One-night stay for two at Amaara Forest Hotel, Sigiriya',
        '50% OFF return air ticket to Maldives by FitsAir',
        'Return air ticket to Maldives by FitsAir',
    ];

    /**
     * Seed the raffle prizes. Safe to run again: existing prizes are left alone.
     */
    public function run(): void
    {
        foreach (self::PRIZES as $index => $name) {
            Prize::firstOrCreate(['name' => $name], ['position' => $index + 1]);
        }
    }
}
