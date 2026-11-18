<?php

use App\Models\Prize;
use App\Models\PrizeDraw;
use App\Models\RaffleEntry;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('access', function () {
    test('guests are sent to the login page', function () {
        $this->get(route('admin.screen'))->assertRedirect(route('admin.login'));
    });

    test('guests cannot read the state or draw', function () {
        Prize::factory()->create();
        RaffleEntry::factory()->create();

        $this->getJson(route('admin.screen.state'))->assertUnauthorized();
        $this->postJson(route('admin.screen.draw'))->assertUnauthorized();

        expect(PrizeDraw::count())->toBe(0);
    });

    test('a signed-in user who is not an admin cannot draw from the screen', function () {
        Prize::factory()->create();
        RaffleEntry::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.screen.draw'))
            ->assertForbidden();

        expect(PrizeDraw::count())->toBe(0);
    });
});

test('the LED screen opens on the logo, with the state and draw URLs', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.screen'))
        ->assertOk()
        ->assertSee('data-scene="intro"', false)
        ->assertSee('data-state-url="'.route('admin.screen.state').'"', false)
        ->assertSee('data-draw-url="'.route('admin.screen.draw').'"', false);
});

describe('state', function () {
    test('it lists prizes lowest first with their winners', function () {
        $lowestPrize = Prize::factory()->create(['name' => 'Dinner for two', 'position' => 1]);
        $grandPrize = Prize::factory()->create(['name' => 'Air ticket to Maldives', 'position' => 2]);
        $winner = RaffleEntry::factory()->create(['name' => 'Nimal Perera', 'ticket_number' => 'T-0042']);
        $draw = PrizeDraw::factory()->for($lowestPrize)->for($winner)->create();

        $this->actingAs($this->admin)
            ->getJson(route('admin.screen.state'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('total_entries', 1)
            ->assertJsonPath('latest_draw', ['id' => $draw->id, 'prize_id' => $lowestPrize->id])
            ->assertJsonPath('prizes', [
                [
                    'id' => $lowestPrize->id,
                    'rank' => 1,
                    'name' => 'Dinner for two',
                    'is_grand_prize' => false,
                    'winner' => ['name' => 'Nimal Perera', 'ticket_number' => 'T-0042'],
                ],
                [
                    'id' => $grandPrize->id,
                    'rank' => 2,
                    'name' => 'Air ticket to Maldives',
                    'is_grand_prize' => true,
                    'winner' => null,
                ],
            ])
            ->assertJsonPath('roll_entries', [['ticket_number' => 'T-0042', 'name' => 'Nimal Perera']]);
    });

    test('it ignores forfeited draws', function () {
        $prize = Prize::factory()->create();
        PrizeDraw::factory()->for($prize)->forfeited()->create();

        $this->actingAs($this->admin)
            ->getJson(route('admin.screen.state'))
            ->assertOk()
            ->assertJsonPath('latest_draw', null)
            ->assertJsonPath('prizes.0.winner', null);
    });

    test('it never includes ID numbers, which the audience must not see', function () {
        $winner = RaffleEntry::factory()->create(['id_number' => '199012345678']);
        PrizeDraw::factory()->for(Prize::factory())->for($winner)->create();

        $response = $this->actingAs($this->admin)->getJson(route('admin.screen.state'))->assertOk();

        expect($response->getContent())->not->toContain('199012345678')
            ->and($response->getContent())->not->toContain('id_number');
    });
});

describe('drawing with Enter', function () {
    test('it draws the lowest prize that has no winner yet and returns the new state', function () {
        $drawnPrize = Prize::factory()->create(['position' => 1]);
        $nextPrize = Prize::factory()->create(['position' => 2]);
        Prize::factory()->create(['position' => 3]);
        PrizeDraw::factory()->for($drawnPrize)->create();
        RaffleEntry::factory()->create(['name' => 'Kamala Silva', 'ticket_number' => 'T-0077']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.screen.draw'))
            ->assertRedirect(route('admin.screen.state'));

        expect($nextPrize->winningDraw->raffleEntry->ticket_number)->toBe('T-0077');

        $this->actingAs($this->admin)
            ->getJson(route('admin.screen.state'))
            ->assertJsonPath('latest_draw.prize_id', $nextPrize->id)
            ->assertJsonPath('prizes.1.winner', ['name' => 'Kamala Silva', 'ticket_number' => 'T-0077']);
    });

    test('it says so when every prize has been drawn', function () {
        PrizeDraw::factory()->for(Prize::factory())->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.screen.draw'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'All prizes have been drawn.');

        expect(PrizeDraw::count())->toBe(1);
    });

    test('it says so when no ticket can win', function () {
        $prize = Prize::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.screen.draw'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'There are no tickets left that can win.');

        expect($prize->winningDraw)->toBeNull();
    });
});
