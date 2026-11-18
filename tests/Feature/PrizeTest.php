<?php

use App\Models\Prize;
use App\Models\User;
use Database\Seeders\PrizeSeeder;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('access', function () {
    test('guests are sent to the login page', function () {
        $prize = Prize::factory()->create();

        $this->get(route('admin.prizes.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.prizes.store'), ['name' => 'Spa day for two'])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.prizes.destroy', $prize))->assertRedirect(route('admin.login'));

        expect(Prize::count())->toBe(1);
    });

    test('a signed-in user who is not an admin is forbidden', function () {
        $prize = Prize::factory()->create(['name' => 'Spa day for two']);
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('admin.prizes.index'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.prizes.update', $prize), ['name' => 'Changed'])->assertForbidden();

        expect($prize->fresh()->name)->toBe('Spa day for two');
    });
});

describe('listing', function () {
    test('prizes are listed from the lowest, with the last marked as the grand prize', function () {
        Prize::factory()->create(['name' => 'Grand prize trip', 'position' => 3]);
        Prize::factory()->create(['name' => 'Lowest prize dinner', 'position' => 1]);
        Prize::factory()->create(['name' => 'Middle prize stay', 'position' => 2]);

        $this->actingAs($this->admin)
            ->get(route('admin.prizes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Lowest prize dinner', 'Middle prize stay', 'Grand prize trip', 'Grand prize']);
    });

    test('the seeder adds the raffle prizes once, in order', function () {
        $this->seed(PrizeSeeder::class);
        $this->seed(PrizeSeeder::class);

        $prizes = Prize::query()->lowestFirst()->pluck('name');

        expect($prizes)->toHaveCount(10)
            ->and($prizes->first())->toBe('Dinner for two at Cinnamon Grand, Colombo')
            ->and($prizes->last())->toBe('Return air ticket to Maldives by FitsAir');
    });
});

describe('managing', function () {
    test('an admin can add a prize, which goes to the end of the list', function () {
        Prize::factory()->create(['position' => 1]);
        Prize::factory()->create(['position' => 2]);

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.store'), ['name' => '  Spa day   for two '])
            ->assertRedirect(route('admin.prizes.index'))
            ->assertSessionHas('status', 'Prize added.');

        expect(Prize::query()->lowestFirst()->get()->last())
            ->name->toBe('Spa day for two')
            ->position->toBe(3);
    });

    test('a prize needs a name', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.prizes.store'), ['name' => '   '])
            ->assertSessionHasErrors(['name' => 'The prize name field is required.']);

        expect(Prize::count())->toBe(0);
    });

    test('an admin can rename a prize', function () {
        $prize = Prize::factory()->create(['name' => 'Old name']);

        $this->actingAs($this->admin)
            ->get(route('admin.prizes.edit', $prize))
            ->assertOk()
            ->assertSee('Old name');

        $this->actingAs($this->admin)
            ->put(route('admin.prizes.update', $prize), ['name' => 'New name'])
            ->assertRedirect(route('admin.prizes.index'))
            ->assertSessionHas('status', 'Prize updated.');

        expect($prize->fresh()->name)->toBe('New name');
    });

    test('an admin can delete a prize', function () {
        $prize = Prize::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.prizes.destroy', $prize))
            ->assertRedirect(route('admin.prizes.index'))
            ->assertSessionHas('status', 'Prize deleted.');

        $this->assertModelMissing($prize);
    });
});

describe('reordering', function () {
    beforeEach(function () {
        $this->first = Prize::factory()->create(['name' => 'First', 'position' => 1]);
        $this->second = Prize::factory()->create(['name' => 'Second', 'position' => 2]);
        $this->third = Prize::factory()->create(['name' => 'Third', 'position' => 3]);
    });

    test('moving a prize swaps it with its neighbour', function (string $direction, array $expectedOrder) {
        $this->actingAs($this->admin)
            ->patch(route('admin.prizes.position.update', $this->second), ['direction' => $direction])
            ->assertRedirect(route('admin.prizes.index'));

        expect(Prize::query()->lowestFirst()->pluck('name')->all())->toBe($expectedOrder);
    })->with([
        'up' => ['up', ['Second', 'First', 'Third']],
        'down' => ['down', ['First', 'Third', 'Second']],
    ]);

    test('moving the first prize up or the last prize down changes nothing', function () {
        $this->actingAs($this->admin)->patch(route('admin.prizes.position.update', $this->first), ['direction' => 'up']);
        $this->actingAs($this->admin)->patch(route('admin.prizes.position.update', $this->third), ['direction' => 'down']);

        expect(Prize::query()->lowestFirst()->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
    });

    test('an unknown direction is rejected', function () {
        $this->actingAs($this->admin)
            ->patch(route('admin.prizes.position.update', $this->second), ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');

        expect(Prize::query()->lowestFirst()->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
    });
});
