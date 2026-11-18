<x-admin-layout title="Raffle entries">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Raffle entries</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-400">
                {{ number_format($totalEntries) }} {{ Str::plural('ticket', $totalEntries) }} registered
            </p>
        </div>

        <form method="GET" action="{{ route('admin.raffle-entries.index') }}" role="search" class="flex w-full gap-2 sm:w-auto">
            <label for="search" class="sr-only">Search entries</label>
            <input
                id="search"
                name="search"
                type="search"
                value="{{ $search }}"
                placeholder="Search name, ID or ticket"
                class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5 outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 sm:w-72 dark:border-stone-700 dark:bg-stone-900"
            >
            <button
                type="submit"
                class="rounded-xl bg-stone-900 px-5 py-2.5 font-medium text-white transition hover:bg-stone-700 dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
            >
                Search
            </button>
        </form>
    </div>

    @if ($search !== '')
        <p class="-mt-4 text-sm text-stone-600 dark:text-stone-400">
            {{ number_format($raffleEntries->total()) }} {{ Str::plural('result', $raffleEntries->total()) }} for “{{ $search }}”.
            <a href="{{ route('admin.raffle-entries.index') }}" class="font-medium text-amber-700 underline-offset-4 hover:underline dark:text-amber-400">Clear search</a>
        </p>
    @endif

    <x-raffle-entries-table
        :raffle-entries="$raffleEntries"
        :empty-message="$search !== '' ? 'No entries match your search.' : 'No entries yet.'"
    />

    {{ $raffleEntries->links() }}
</x-admin-layout>
