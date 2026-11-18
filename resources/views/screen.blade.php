@php
    $logoUrl = asset(rawurlencode(config('raffle.branding.logo')));
    $backgroundUrl = asset(rawurlencode(config('raffle.branding.background')));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-black">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Winners · {{ config('raffle.name') }}</title>

        @fonts

        @vite(['resources/css/screen.css', 'resources/js/screen.js'])
    </head>
    {{--
        Built for a 20ft × 8ft (5:2) LED wall. The stage keeps 5:2 and scales to any resolution.
        Keys: Enter / Space / Page Down / → draw the next prize, L shows the logo, double-click toggles full screen.
    --}}
    <body class="flex h-full cursor-none items-center justify-center overflow-hidden bg-black text-white antialiased select-none">
        <div
            data-stage
            data-scene="intro"
            data-state-url="{{ route('admin.screen.state') }}"
            data-draw-url="{{ route('admin.screen.draw') }}"
            class="group/stage @container relative aspect-[5/2] w-[min(100vw,250vh)] overflow-hidden bg-[#06122b] bg-cover bg-center"
            style="background-image: url('{{ $backgroundUrl }}')"
        >
            {{-- Ambient background --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_50%,transparent_35%,rgba(2,6,23,0.65))]"></div>
                {{-- Centred on the logo, then on the draw column (45.25% across) during the draw. --}}
                <div data-rays class="screen-rays absolute top-1/2 left-1/2 size-[120cqw] -translate-x-1/2 -translate-y-1/2 transition-[left] duration-1000 group-data-[scene=draw]/stage:left-[45.25%]"></div>
                <div class="screen-glow absolute top-1/2 left-1/2 size-[60cqw] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(circle,rgba(245,158,11,0.22),transparent_62%)] transition-[left] duration-1000 group-data-[scene=draw]/stage:left-[45.25%]"></div>
                <div class="absolute -top-[22cqw] -right-[12cqw] size-[50cqw] rounded-full bg-[radial-gradient(circle,rgba(59,130,246,0.16),transparent_62%)]"></div>
                <div data-sparkles class="absolute inset-0"></div>
            </div>

            {{-- Draw scene: logo | live draw | winners board --}}
            <div
                data-draw
                class="pointer-events-none absolute inset-0 z-10 grid grid-cols-[20cqw_minmax(0,1fr)_29.5cqw] gap-[2.6cqw] p-[2.4cqw] opacity-0 transition-opacity duration-700 group-data-[scene=draw]/stage:opacity-100"
            >
                <div class="flex flex-col items-center justify-center text-center">
                    <img
                        data-side-logo
                        src="{{ $logoUrl }}"
                        alt=""
                        width="2152"
                        height="2032"
                        class="w-[19cqw] opacity-0 drop-shadow-[0_0_2.5cqw_rgba(251,191,36,0.35)]"
                    >
                    <p class="mt-[1.4cqw] text-[0.85cqw] font-semibold tracking-[0.18em] whitespace-nowrap text-amber-300 uppercase">{{ config('raffle.name') }}</p>
                    <p class="mt-[0.5cqw] text-[1cqw] text-white/60">
                        <span data-total-entries class="font-semibold text-white tabular-nums">0</span> tickets in the draw
                    </p>
                </div>

                <section data-main data-mode="idle" class="group/main flex min-w-0 flex-col items-center justify-center text-center">
                    <p
                        data-label
                        class="font-display text-[1.6cqw] font-bold tracking-[0.2em] text-amber-300 uppercase group-data-[mode=drawing]/main:animate-pulse"
                    ></p>
                    <p data-rank class="mt-[0.5cqw] text-[1.3cqw] font-medium text-white/65"></p>
                    <h1 data-prize class="mt-[0.2cqw] text-[3cqw] leading-[1.1] font-bold text-balance"></h1>

                    <div
                        data-ticket-frame
                        class="relative mt-[1.6cqw] flex h-[9cqw] w-full items-center justify-center overflow-hidden rounded-[1.6cqw] border-[0.2cqw] border-white/15 bg-[#020617]/40 backdrop-blur-sm transition-[border-color,background-color,box-shadow] duration-700 group-data-[mode=drawing]/main:border-amber-300/60 group-data-[mode=drawing]/main:shadow-[0_0_4cqw_rgba(251,191,36,0.25)] group-data-[mode=revealed]/main:border-amber-300 group-data-[mode=revealed]/main:bg-amber-300/10 group-data-[mode=revealed]/main:shadow-[0_0_7cqw_rgba(251,191,36,0.45)]"
                    >
                        <span aria-hidden="true" class="screen-ticket-beam"></span>
                        <span
                            data-ticket
                            class="relative block py-[0.4cqw] text-[6.2cqw] leading-none font-bold tracking-[0.03em] whitespace-nowrap text-white/20 tabular-nums group-data-[mode=drawing]/main:text-white group-data-[mode=revealed]/main:bg-linear-to-b group-data-[mode=revealed]/main:from-amber-50 group-data-[mode=revealed]/main:to-amber-400 group-data-[mode=revealed]/main:bg-clip-text group-data-[mode=revealed]/main:text-transparent"
                        ></span>
                    </div>

                    <p data-winner class="mt-[1.2cqw] min-h-[4.4cqw] text-[4cqw] leading-[1.1] font-bold text-balance group-data-[mode=drawing]/main:text-white/65"></p>
                </section>

                <aside data-board-panel class="screen-board flex min-h-0 flex-col rounded-[1.4cqw] border border-amber-200/15 bg-[#020617]/45 px-[1.3cqw] py-[1.2cqw] backdrop-blur-sm">
                    <div class="flex items-baseline justify-between">
                        <h2 class="screen-board-title font-display text-[1.6cqw] font-bold">Winners</h2>
                        <p data-progress class="text-[1cqw] text-white/50 tabular-nums"></p>
                    </div>
                    <div class="mt-[0.6cqw] h-[0.22cqw] overflow-hidden rounded-full bg-white/10">
                        <div data-progress-bar class="screen-progress-fill h-full w-0 rounded-full"></div>
                    </div>
                    <ol data-board class="-mx-[0.5cqw] mt-[0.4cqw] flex min-h-0 flex-1 flex-col justify-between overflow-hidden px-[0.5cqw] py-[0.3cqw] text-[1.1cqw]"></ol>
                    <div data-board-sparks aria-hidden="true" class="pointer-events-none absolute inset-0"></div>
                </aside>
            </div>

            {{-- Intro scene: the animated logo. It flies to the side when the first draw starts. --}}
            <div data-intro class="pointer-events-none absolute inset-0 z-20 flex flex-col items-center justify-center">
                <div data-hero class="relative w-[30cqw]">
                    <div aria-hidden="true" class="absolute top-1/2 left-1/2 size-[46cqw] -translate-x-1/2 -translate-y-1/2 transition-opacity duration-500 group-data-[scene=draw]/stage:opacity-0">
                        <div class="screen-glow size-full rounded-full bg-[radial-gradient(circle,rgba(251,191,36,0.3),transparent_60%)]"></div>
                    </div>
                    <div class="screen-float relative">
                        <img
                            data-hero-logo
                            src="{{ $logoUrl }}"
                            alt="{{ config('raffle.branding.logo_alt') }}"
                            width="2152"
                            height="2032"
                            class="w-full drop-shadow-[0_0_3cqw_rgba(251,191,36,0.4)]"
                        >
                        <div
                            aria-hidden="true"
                            class="screen-shine absolute inset-0 transition-opacity duration-500 group-data-[scene=draw]/stage:opacity-0"
                            style="--logo-url: url('{{ $logoUrl }}')"
                        ></div>
                    </div>
                </div>
                <p
                    data-intro-caption
                    class="mt-[1.4cqw] font-display text-[1.7cqw] font-bold tracking-[0.3em] text-amber-200 uppercase transition-opacity duration-500 group-data-[scene=draw]/stage:opacity-0"
                >
                    {{ config('raffle.name') }}
                </p>
            </div>

            {{-- Grand prize takeover: dims the wall, sweeps spotlights and announces the final draw. --}}
            <div
                data-grand
                aria-hidden="true"
                class="pointer-events-none invisible absolute inset-0 z-[35] flex flex-col items-center justify-center overflow-hidden text-center opacity-0"
            >
                <div class="absolute inset-0 bg-[#01040f]/95"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_55%,rgba(245,158,11,0.22),transparent_55%)]"></div>
                <div class="screen-spotlight absolute -bottom-[4cqw] left-[12%] h-[58cqw] w-[16cqw]"></div>
                <div class="screen-spotlight screen-spotlight-reverse absolute right-[12%] -bottom-[4cqw] h-[58cqw] w-[16cqw]"></div>
                <p data-grand-eyebrow class="relative font-display text-[1.6cqw] font-bold tracking-[0.5em] text-amber-200/90 uppercase opacity-0">
                    And now, the final draw
                </p>
                <h2 data-grand-title class="relative mt-[1cqw] font-display text-[8cqw] leading-none font-bold uppercase"></h2>
                <p data-grand-prize-name class="relative mt-[1.6cqw] max-w-[70cqw] text-[2.6cqw] font-bold text-balance text-white opacity-0"></p>
            </div>

            <div data-rings aria-hidden="true" class="pointer-events-none absolute inset-0 z-[36] overflow-hidden"></div>

            <div
                data-flash
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 z-30 bg-[radial-gradient(circle_at_var(--flash-x)_55%,rgba(253,230,138,0.85),rgba(245,158,11,0.25)_35%,transparent_70%)] opacity-0 [--flash-x:50%] group-data-[scene=draw]/stage:[--flash-x:45.25%]"
            ></div>

            <canvas data-fireworks aria-hidden="true" class="pointer-events-none absolute inset-0 z-40 size-full"></canvas>
            <canvas data-confetti aria-hidden="true" class="pointer-events-none absolute inset-0 z-40 size-full"></canvas>

            <p
                data-toast
                role="status"
                class="pointer-events-none absolute bottom-[2.4cqw] left-1/2 z-50 -translate-x-1/2 rounded-full border border-amber-200/30 bg-[#020617]/80 px-[2cqw] py-[0.7cqw] text-[1.3cqw] font-semibold text-amber-100 opacity-0 backdrop-blur-sm"
            ></p>
        </div>
    </body>
</html>
