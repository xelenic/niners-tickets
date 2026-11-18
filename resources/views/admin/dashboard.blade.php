@php
    $stats = [
        ['label' => 'Total entries', 'value' => $totalEntries],
        ['label' => 'Entries today', 'value' => $entriesToday],
        ['label' => 'Unique entrants', 'value' => $uniqueEntrants, 'note' => 'Counted by NIC / ID number'],
        ['label' => 'Prizes', 'value' => $prizeCount],
    ];
@endphp

<x-admin-layout title="Dashboard">
    <div>
        <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-400">{{ config('raffle.name') }}</p>
    </div>

    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <dt class="text-sm text-stone-600 dark:text-stone-400">{{ $stat['label'] }}</dt>
                <dd class="order-first text-3xl font-semibold tracking-tight tabular-nums">{{ number_format($stat['value']) }}</dd>
                @isset($stat['note'])
                    <dd class="text-xs text-stone-500 dark:text-stone-500">{{ $stat['note'] }}</dd>
                @endisset
            </div>
        @endforeach
    </dl>

    <section aria-labelledby="latest-entries-heading" class="flex flex-col gap-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="latest-entries-heading" class="text-lg font-semibold">Latest entries</h2>
            <a
                href="{{ route('admin.raffle-entries.index') }}"
                class="text-sm font-medium text-amber-700 underline-offset-4 hover:underline dark:text-amber-400"
            >
                View all entries
            </a>
        </div>

        <x-raffle-entries-table :raffle-entries="$latestEntries" />
    </section>
</x-admin-layout>
