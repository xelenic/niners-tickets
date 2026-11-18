@props(['title'])

@php
    $navigationLinks = [
        'admin.dashboard' => 'Dashboard',
        'admin.raffle-entries.index' => 'Raffle entries',
        'admin.prizes.*' => 'Prizes',
        'admin.draw.*' => 'Draw',
    ];
@endphp

<x-layout :title="$title.' · Admin'">
    <header class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-8 gap-y-3 px-6 py-4 lg:px-8">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                <span class="size-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                Raffle admin
            </a>

            <nav aria-label="Admin" class="order-last flex w-full gap-1 sm:order-none sm:w-auto">
                @foreach ($navigationLinks as $routePattern => $label)
                    @php($isCurrent = request()->routeIs($routePattern))
                    <a
                        href="{{ route(str_replace('*', 'index', $routePattern)) }}"
                        @if ($isCurrent) aria-current="page" @endif
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-stone-100 text-stone-900 dark:bg-stone-800 dark:text-white' => $isCurrent,
                            'text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-white' => ! $isCurrent,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-4 text-sm">
                <a
                    href="{{ route('raffle-entries.create') }}"
                    target="_blank"
                    class="text-stone-600 underline-offset-4 hover:text-stone-900 hover:underline dark:text-stone-400 dark:hover:text-white"
                >
                    Open kiosk
                </a>
                <span class="hidden text-stone-400 sm:inline dark:text-stone-600" aria-hidden="true">|</span>
                <span class="hidden text-stone-600 sm:inline dark:text-stone-400">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-stone-300 px-3 py-1.5 font-medium transition hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800"
                    >
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-6xl flex-col gap-8 px-6 py-10 lg:px-8">
        @if (session('status'))
            <p
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
                {{ session('status') }}
            </p>
        @endif

        {{ $slot }}
    </main>
</x-layout>
