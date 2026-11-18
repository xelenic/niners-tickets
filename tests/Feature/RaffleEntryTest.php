<?php

use App\Http\Requests\StoreRaffleEntryRequest;
use App\Models\Prize;
use App\Models\RaffleEntry;

describe('kiosk', function () {
    test('the kiosk shows only the entry form, without the prizes', function () {
        Prize::factory()->create(['name' => 'Return air ticket to Maldives by FitsAir']);

        $this->get(route('raffle-entries.create'))
            ->assertOk()
            ->assertSee(['Full name', 'NIC / ID number', 'Ticket number', 'Enter the draw'])
            ->assertDontSee('Return air ticket to Maldives by FitsAir')
            ->assertDontSee('Grand prize');
    });

    test('a valid entry is stored normalized and the entrant sees a confirmation', function () {
        $this->followingRedirects()
            ->post(route('raffle-entries.store'), [
                'name' => '  Nimal   Perera ',
                'id_number' => '199012345678',
                'ticket_number' => ' t-00 42 ',
            ])
            ->assertOk()
            ->assertSee("You're in the draw, Nimal Perera!", false)
            ->assertSee('T-0042');

        $this->assertDatabaseHas('raffle_entries', [
            'name' => 'Nimal Perera',
            'id_number' => '199012345678',
            'ticket_number' => 'T-0042',
        ]);
    });

    test('the same person can enter more than one ticket', function () {
        RaffleEntry::factory()->create(['id_number' => '901234567V', 'ticket_number' => 'T-0001']);

        $this->post(route('raffle-entries.store'), [
            'name' => 'Nimal Perera',
            'id_number' => '901234567v',
            'ticket_number' => 'T-0002',
        ])->assertSessionHasNoErrors();

        expect(RaffleEntry::where('id_number', '901234567V')->count())->toBe(2);
    });

    test('every field is required', function () {
        $this->post(route('raffle-entries.store'), [])
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'id_number' => 'The ID number field is required.',
                'ticket_number' => 'The ticket number field is required.',
            ]);

        expect(RaffleEntry::count())->toBe(0);
    });

    test('a ticket number cannot be entered twice, regardless of case or spacing', function () {
        RaffleEntry::factory()->create(['ticket_number' => 'T-0042']);

        $this->post(route('raffle-entries.store'), [
            'name' => 'Kamala Silva',
            'id_number' => '199512345678',
            'ticket_number' => 't-00 42',
        ])->assertSessionHasErrors(['ticket_number' => StoreRaffleEntryRequest::DUPLICATE_TICKET_MESSAGE]);

        expect(RaffleEntry::count())->toBe(1);
    });

    test('the ID number only accepts letters and numbers', function () {
        $this->post(route('raffle-entries.store'), [
            'name' => 'Kamala Silva',
            'id_number' => '1995-1234',
            'ticket_number' => 'T-0042',
        ])->assertSessionHasErrors(['id_number' => 'The ID number may only contain letters and numbers.']);
    });

    test('the ticket number only accepts letters, numbers and dashes', function () {
        $this->post(route('raffle-entries.store'), [
            'name' => 'Kamala Silva',
            'id_number' => '199512345678',
            'ticket_number' => 'T/0042',
        ])->assertSessionHasErrors(['ticket_number' => 'The ticket number may only contain letters, numbers and dashes.']);
    });
});
