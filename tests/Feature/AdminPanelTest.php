<?php

use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

describe('logging in', function () {
    test('guests are sent to the login page', function (string $routeName) {
        $this->get(route($routeName))->assertRedirect(route('admin.login'));
    })->with(['admin.dashboard', 'admin.raffle-entries.index']);

    test('the login page is shown to guests', function () {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Admin log in');
    });

    test('an admin can log in and lands on the dashboard', function () {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('admin.login.store'), [
            'email' => ' Admin@Example.com ',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    });

    test('a wrong password is rejected', function () {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    });

    test('a user who is not an admin cannot log in', function () {
        User::factory()->create(['email' => 'staff@example.com']);

        $this->post(route('admin.login.store'), [
            'email' => 'staff@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    });

    test('login attempts are rate limited', function () {
        User::factory()->admin()->create(['email' => 'admin@example.com']);
        $credentials = ['email' => 'admin@example.com', 'password' => 'wrong-password'];

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), $credentials)->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login.store'), $credentials)->assertTooManyRequests();
    });

    test('a logged-in admin visiting the login page goes to the dashboard', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    });

    test('an admin can log out', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    });
});

describe('access', function () {
    test('a signed-in user who is not an admin is forbidden', function (string $routeName) {
        $this->actingAs(User::factory()->create())
            ->get(route($routeName))
            ->assertForbidden();
    })->with(['admin.dashboard', 'admin.raffle-entries.index']);
});

describe('dashboard', function () {
    test('it shows entry totals, counting today in the raffle timezone', function () {
        // Timestamps are stored in UTC. 03:00 on 19 Nov in Colombo is still 18 Nov in UTC,
        // so an entry from 23:00 Colombo time on 18 Nov must not count as today.
        $colomboTime = fn (string $time): Carbon => Carbon::parse($time, 'Asia/Colombo')->utc();

        $this->travelTo($colomboTime('2026-11-19 03:00'));

        RaffleEntry::factory()->create(['id_number' => '199012345678', 'created_at' => $colomboTime('2026-11-19 01:00')]);
        RaffleEntry::factory()->create(['id_number' => '199012345678', 'created_at' => $colomboTime('2026-11-18 23:00')]);
        RaffleEntry::factory()->create(['id_number' => '200098765432', 'created_at' => $colomboTime('2026-11-17 12:00')]);
        Prize::factory()->count(4)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalEntries', 3)
            ->assertViewHas('entriesToday', 1)
            ->assertViewHas('uniqueEntrants', 2)
            ->assertViewHas('prizeCount', 4);
    });

    test('it lists the latest entries first', function () {
        RaffleEntry::factory()->create(['name' => 'Older Entrant', 'created_at' => now()->subHour()]);
        RaffleEntry::factory()->create(['name' => 'Newer Entrant', 'created_at' => now()]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Newer Entrant', 'Older Entrant']);
    });
});

describe('raffle entries list', function () {
    test('it lists every entry', function () {
        RaffleEntry::factory()->create(['name' => 'Nimal Perera', 'id_number' => '199012345678', 'ticket_number' => 'T-0001']);
        RaffleEntry::factory()->create(['name' => 'Kamala Silva', 'ticket_number' => 'T-0002']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.raffle-entries.index'))
            ->assertOk()
            ->assertSee('2 tickets registered')
            ->assertSee(['Nimal Perera', '199012345678', 'T-0001', 'Kamala Silva', 'T-0002']);
    });

    test('it filters entries by search term', function () {
        RaffleEntry::factory()->create(['name' => 'Nimal Perera', 'ticket_number' => 'T-0001']);
        RaffleEntry::factory()->create(['name' => 'Kamala Silva', 'ticket_number' => 'T-0002']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.raffle-entries.index', ['search' => 'T-0002']))
            ->assertOk()
            ->assertSee('1 result for')
            ->assertSee('Kamala Silva')
            ->assertDontSee('Nimal Perera');
    });

    test('it escapes entrant names', function () {
        RaffleEntry::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.raffle-entries.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    });
});
