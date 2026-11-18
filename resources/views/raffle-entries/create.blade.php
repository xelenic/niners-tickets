@php
    $logoUrl = asset(rawurlencode(config('raffle.branding.logo')));
    $backgroundUrl = asset(rawurlencode(config('raffle.branding.background')));
    $goldButtonClasses = 'rounded-2xl bg-linear-to-b from-amber-200 via-amber-300 to-amber-500 font-semibold text-[#0b1a3a] shadow-[0_0.5rem_2rem_rgba(251,191,36,0.3)] transition hover:brightness-105 focus:ring-4 focus:ring-amber-300/40 focus:outline-none';
@endphp

<x-layout>
    <main
        class="relative isolate flex min-h-screen touch-manipulation items-center justify-center overflow-hidden bg-[#06122b] bg-cover bg-center px-6 py-10 text-white kiosk:px-24 kiosk:py-20"
        style="background-image: url('{{ $backgroundUrl }}')"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_50%_15%,rgba(251,191,36,0.18),transparent_55%),linear-gradient(to_bottom,rgba(2,6,23,0.1),rgba(2,6,23,0.6))]"
        ></div>

        <section aria-labelledby="entry-heading" class="flex w-full max-w-xl flex-col items-center kiosk:max-w-none">
            <img
                src="{{ $logoUrl }}"
                alt="{{ config('raffle.branding.logo_alt') }}"
                width="2152"
                height="2032"
                class="w-40 drop-shadow-[0_0_2.5rem_rgba(251,191,36,0.3)] sm:w-48 kiosk:w-[26rem]"
            >

            <p class="mt-6 text-center text-xs font-semibold tracking-[0.3em] text-amber-300 uppercase sm:text-sm kiosk:mt-12 kiosk:text-2xl">
                {{ config('raffle.name') }}
            </p>
            <h1 id="entry-heading" class="mt-3 text-center font-display text-3xl font-bold text-balance sm:text-4xl kiosk:mt-5 kiosk:text-6xl">
                Enter the raffle draw
            </h1>
            <p class="mt-3 text-center text-base text-pretty text-white/70 sm:text-lg kiosk:mt-6 kiosk:text-3xl kiosk:leading-snug">
                Fill in your details and your ticket number to enter the draw.
            </p>

            @if ($registeredEntry = session('registered_entry'))
                <div
                    role="status"
                    data-success-panel
                    class="mt-8 flex w-full flex-col items-center gap-4 rounded-3xl border border-amber-300/40 bg-[#020617]/40 p-8 text-center shadow-[0_0_4rem_rgba(251,191,36,0.15)] backdrop-blur-md sm:p-10 kiosk:mt-16 kiosk:gap-8 kiosk:rounded-[2.5rem] kiosk:border-2 kiosk:p-16"
                >
                    <svg class="size-16 text-amber-300 kiosk:size-32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m7.5 12.5 3 3 6-6.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h2 class="text-3xl font-bold tracking-tight text-balance kiosk:text-6xl">
                        You're in the draw, {{ $registeredEntry['name'] }}!
                    </h2>
                    <p class="text-lg text-white/75 kiosk:text-3xl kiosk:leading-snug">
                        Ticket <span class="font-semibold text-amber-300">{{ $registeredEntry['ticket_number'] }}</span> has been registered. Good luck!
                    </p>
                    <a
                        href="{{ route('raffle-entries.create') }}"
                        class="{{ $goldButtonClasses }} mt-4 inline-flex items-center justify-center px-10 py-4 text-lg kiosk:mt-6 kiosk:rounded-3xl kiosk:px-16 kiosk:py-8 kiosk:text-4xl"
                    >
                        Next entry
                    </a>
                    <p class="text-sm text-white/50 kiosk:text-xl">This screen resets automatically in a few seconds.</p>
                </div>
            @else
                <form
                    method="POST"
                    action="{{ route('raffle-entries.store') }}"
                    autocomplete="off"
                    data-kiosk-form
                    class="mt-8 flex w-full flex-col gap-5 rounded-3xl border border-white/10 bg-[#020617]/40 p-6 shadow-2xl backdrop-blur-md sm:p-8 kiosk:mt-16 kiosk:gap-10 kiosk:rounded-[2.5rem] kiosk:p-12"
                >
                    @csrf

                    <x-kiosk-input
                        name="name"
                        label="Full name"
                        maxlength="100"
                        autocapitalize="words"
                        enterkeyhint="next"
                        required
                        autofocus
                    />

                    <x-kiosk-input
                        name="id_number"
                        label="NIC / ID number"
                        hint="Your national identity card or passport number."
                        maxlength="20"
                        autocapitalize="characters"
                        spellcheck="false"
                        enterkeyhint="next"
                        required
                    />

                    <x-kiosk-input
                        name="ticket_number"
                        label="Ticket number"
                        hint="Printed on your raffle ticket."
                        maxlength="30"
                        autocapitalize="characters"
                        spellcheck="false"
                        enterkeyhint="done"
                        required
                    />

                    <button
                        type="submit"
                        class="{{ $goldButtonClasses }} mt-2 px-8 py-5 text-xl disabled:cursor-wait disabled:opacity-60 kiosk:mt-4 kiosk:rounded-3xl kiosk:py-9 kiosk:text-4xl kiosk:focus:ring-8"
                    >
                        Enter the draw
                    </button>
                </form>
            @endif
        </section>
    </main>

    <script>
        (() => {
            const resetUrl = @js(route('raffle-entries.create'));
            const successPanelResetDelay = 8 * 1000;
            const abandonedFormResetDelay = 2 * 60 * 1000;
            const idlePageRefreshDelay = 30 * 60 * 1000;

            if (document.querySelector('[data-success-panel]')) {
                setTimeout(() => window.location.replace(resetUrl), successPanelResetDelay);

                return;
            }

            const form = document.querySelector('[data-kiosk-form]');

            if (! form) {
                return;
            }

            // After a rejected submission, put the cursor in the field that needs fixing.
            form.querySelector('[aria-invalid="true"]')?.focus();

            form.addEventListener('submit', () => {
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Submitting…';
            });

            let lastActivityAt = Date.now();

            ['pointerdown', 'keydown', 'input'].forEach((eventName) => {
                document.addEventListener(eventName, () => {
                    lastActivityAt = Date.now();
                }, { passive: true });
            });

            // Clear half-filled forms someone walked away from, and refresh an idle
            // kiosk before its session (and CSRF token) expires.
            setInterval(() => {
                const idleFor = Date.now() - lastActivityAt;
                const hasTypedSomething = [...form.querySelectorAll('input[type="text"]')].some((input) => input.value !== '');

                if ((hasTypedSomething && idleFor > abandonedFormResetDelay) || idleFor > idlePageRefreshDelay) {
                    window.location.replace(resetUrl);
                }
            }, 15 * 1000);
        })();
    </script>
</x-layout>
