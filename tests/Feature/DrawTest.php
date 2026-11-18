<?php

use App\Models\Prize;
use App\Models\PrizeDraw;
use App\Models\RaffleEntry;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->lowestPrize = Prize::factory()->create(['name' => 'Dinner for two', 'position' => 1]);
    $this->grandPrize = Prize::factory()->create(['name' => 'Air ticket to Maldives', 'position' => 2]);
});

describe('access', function () {
    test('guests are sent to the login page and cannot draw', function () {
        RaffleEntry::factory()->create();

        $this->get(route('admin.draw.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.prizes.draws.store', $this->lowestPrize))->assertRedirect(route('admin.login'));

        expect(PrizeDraw::count())->toBe(0);
    });

    test('a signed-in user who is not an admin cannot draw', function () {
        RaffleEntry::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.prizes.draws.store', $this->lowestPrize))
            ->assertForbidden();

        expect(PrizeDraw::count())->toBe(0);
    });
});

describe('drawing', function () {
    test('the draw page lists prizes and offers to draw the lowest one first', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.draw.index'))
            ->assertOk()
            ->assertSeeInOrder(['Dinner for two', 'Draw winner', 'Air ticket to Maldives', 'Waiting']);
    });

    test('drawing picks an eligible ticket as the winner', function () {
        $entry = RaffleEntry::factory()->create(['name' => 'Nimal Perera', 'ticket_number' => 'T-0042']);

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->lowestPrize))
            ->assertRedirect(route('admin.draw.index'))
            ->assertSessionHas('status', 'Nimal Perera (ticket T-0042) won “Dinner for two”. The LED screen is revealing it now.');

        expect($this->lowestPrize->winningDraw->raffle_entry_id)->toBe($entry->id);
    });

    test('prizes must be drawn from the lowest up', function () {
        RaffleEntry::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->grandPrize))
            ->assertSessionHasErrors(['draw' => 'Prizes are drawn from the lowest up. Draw “Dinner for two” next.']);

        expect(PrizeDraw::count())->toBe(0);
    });

    test('drawing a prize that already has a winner does not replace the winner', function () {
        RaffleEntry::factory()->count(2)->create();
        $existingDraw = $this->lowestPrize->drawWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->lowestPrize))
            ->assertSessionHasErrors(['draw' => '“Dinner for two” already has a winner.']);

        expect($this->lowestPrize->winningDraw->is($existingDraw))->toBeTrue()
            ->and(PrizeDraw::count())->toBe(1);
    });

    test('a person cannot win twice, even with several tickets', function () {
        RaffleEntry::factory()->count(3)->create(['id_number' => '199012345678']);
        $otherPerson = RaffleEntry::factory()->create(['id_number' => '200098765432']);

        $this->actingAs($this->admin)->post(route('admin.prizes.draws.store', $this->lowestPrize));
        $this->actingAs($this->admin)->post(route('admin.prizes.draws.store', $this->grandPrize));

        $winningIdNumbers = PrizeDraw::with('raffleEntry')->get()->pluck('raffleEntry.id_number')->sort()->values()->all();

        expect($winningIdNumbers)->toBe(['199012345678', '200098765432'])
            ->and($otherPerson->draws()->count())->toBe(1);
    });

    test('drawing fails clearly when no ticket can win', function () {
        $entry = RaffleEntry::factory()->create();
        PrizeDraw::factory()->for($this->lowestPrize)->for($entry)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->grandPrize))
            ->assertSessionHasErrors(['draw' => 'There are no tickets left that can win. Each person can only win one prize.']);

        expect($this->grandPrize->winningDraw)->toBeNull();
    });
});

describe('redrawing', function () {
    test('redrawing forfeits the current winner, who cannot be drawn again', function () {
        $absentWinner = RaffleEntry::factory()->create(['id_number' => '199012345678']);
        RaffleEntry::factory()->create(['id_number' => '199012345678']);
        $presentEntrant = RaffleEntry::factory()->create(['id_number' => '200098765432']);
        $forfeitedDraw = PrizeDraw::factory()->for($this->lowestPrize)->for($absentWinner)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->lowestPrize), ['redraw' => '1'])
            ->assertRedirect(route('admin.draw.index'));

        expect($forfeitedDraw->fresh()->forfeited_at)->not->toBeNull()
            ->and($this->lowestPrize->winningDraw->raffle_entry_id)->toBe($presentEntrant->id);
    });

    test('a prize without a winner cannot be redrawn', function () {
        RaffleEntry::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.prizes.draws.store', $this->lowestPrize), ['redraw' => '1'])
            ->assertSessionHasErrors(['draw' => '“Dinner for two” has no winner to redraw.']);

        expect(PrizeDraw::count())->toBe(0);
    });
});

test('resetting clears every draw', function () {
    PrizeDraw::factory()->for($this->lowestPrize)->create();
    PrizeDraw::factory()->for($this->grandPrize)->forfeited()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.draw.destroy'))
        ->assertRedirect(route('admin.draw.index'))
        ->assertSessionHas('status', 'All draws have been reset.');

    expect(PrizeDraw::count())->toBe(0);
});
