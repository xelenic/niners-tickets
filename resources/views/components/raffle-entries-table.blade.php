@props(['raffleEntries', 'emptyMessage' => 'No entries yet.'])

<div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-stone-200 bg-stone-100 text-xs tracking-wide text-stone-600 uppercase dark:border-stone-800 dark:bg-stone-800/50 dark:text-stone-400">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Ticket number</th>
                <th scope="col" class="px-4 py-3 font-semibold">Name</th>
                <th scope="col" class="px-4 py-3 font-semibold">NIC / ID number</th>
                <th scope="col" class="px-4 py-3 font-semibold">Registered</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
            @forelse ($raffleEntries as $raffleEntry)
                <tr class="hover:bg-stone-50 dark:hover:bg-stone-800/40">
                    <td class="px-4 py-3 font-mono font-medium whitespace-nowrap">{{ $raffleEntry->ticket_number }}</td>
                    <td class="px-4 py-3">{{ $raffleEntry->name }}</td>
                    <td class="px-4 py-3 font-mono whitespace-nowrap">{{ $raffleEntry->id_number }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-stone-500 tabular-nums dark:text-stone-400">
                        {{ $raffleEntry->created_at->timezone(config('raffle.timezone'))->format('d M Y, g:i A') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-stone-500 dark:text-stone-400">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
