@php
    $drawnCount = $prizes->filter(fn ($prize) => $prize->winningDraw)->count();
    $stats = [
        ['label' => 'Tickets in the draw', 'value' => $totalEntries],
        ['label' => 'Tickets that can still win', 'value' => $eligibleEntries],
        ['label' => 'Prizes drawn', 'value' => $drawnCount, 'total' => $prizes->count()],
    ];
    $disableOnSubmit = "this.querySelector('button[type=submit]').disabled = true";
@endphp

<x-admin-layout title="Draw">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Draw</h1>
            <p class="mt-1 max-w-2xl text-stone-600 dark:text-stone-400">
                Prizes are drawn from the lowest up to the grand prize. Each person (NIC / ID number) can win only one prize.
            </p>
            <p class="mt-2 max-w-2xl text-sm text-stone-500 dark:text-stone-400">
                On the LED screen, press <kbd class="rounded border border-stone-300 px-1.5 py-0.5 font-mono text-xs dark:border-stone-700">Enter</kbd> to draw the next prize and
                <kbd class="rounded border border-stone-300 px-1.5 py-0.5 font-mono text-xs dark:border-stone-700">L</kbd> to go back to the logo. You can also draw from the buttons below.
            </p>
        </div>

        <a
            href="{{ route('admin.screen') }}"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-xl bg-stone-900 px-5 py-2.5 font-medium text-white transition hover:bg-stone-700 dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
        >
            Open LED screen
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 4H4v12h12v-4M11 3h6v6M17 3l-8 8" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </a>
    </div>

    @error('draw')
        <p role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </p>
    @enderror

    <dl class="grid gap-4 sm:grid-cols-3">
        @foreach ($stats as $stat)
            <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <dt class="text-sm text-stone-600 dark:text-stone-400">{{ $stat['label'] }}</dt>
                <dd class="order-first text-3xl font-semibold tracking-tight tabular-nums">
                    {{ number_format($stat['value']) }}@isset($stat['total'])<span class="text-xl text-stone-400 dark:text-stone-500"> / {{ number_format($stat['total']) }}</span>@endisset
                </dd>
            </div>
        @endforeach
    </dl>

    <div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-100 text-xs tracking-wide text-stone-600 uppercase dark:border-stone-800 dark:bg-stone-800/50 dark:text-stone-400">
                <tr>
                    <th scope="col" class="w-14 px-4 py-3 font-semibold">#</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Prize</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Winner</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                @forelse ($prizes as $prize)
                    @php($winner = $prize->winningDraw?->raffleEntry)
                    <tr @class(['bg-amber-50/60 dark:bg-amber-950/20' => $prize->is($nextPrize)])>
                        <td class="px-4 py-4 text-stone-500 tabular-nums dark:text-stone-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $prize->name }}</span>
                                @if ($loop->last)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Grand prize</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            @if ($winner)
                                <p class="font-semibold">{{ $winner->name }}</p>
                                <p class="mt-0.5 font-mono text-xs text-stone-500 dark:text-stone-400">
                                    Ticket {{ $winner->ticket_number }} · NIC {{ $winner->id_number }}
                                </p>
                            @else
                                <span class="text-stone-400 dark:text-stone-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right whitespace-nowrap">
                            @if ($winner)
                                <form
                                    method="POST"
                                    action="{{ route('admin.prizes.draws.store', $prize) }}"
                                    onsubmit="if (! confirm({{ Js::from('Redraw “'.$prize->name.'”? '.$winner->name.' forfeits this prize and cannot win again.') }})) { return false; } {{ $disableOnSubmit }}"
                                >
                                    @csrf
                                    <input type="hidden" name="redraw" value="1">
                                    <button
                                        type="submit"
                                        class="rounded-lg border border-stone-300 px-3 py-2 font-medium transition hover:bg-stone-100 disabled:opacity-50 dark:border-stone-700 dark:hover:bg-stone-800"
                                    >
                                        Redraw
                                    </button>
                                </form>
                            @elseif ($prize->is($nextPrize))
                                <form method="POST" action="{{ route('admin.prizes.draws.store', $prize) }}" onsubmit="{{ $disableOnSubmit }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-amber-500 px-4 py-2 font-semibold text-stone-950 shadow-sm transition hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60"
                                    >
                                        Draw winner
                                    </button>
                                </form>
                            @else
                                <span class="text-stone-400 dark:text-stone-600">Waiting</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-stone-500 dark:text-stone-400">
                            No prizes yet. <a href="{{ route('admin.prizes.index') }}" class="font-medium text-amber-700 underline-offset-4 hover:underline dark:text-amber-400">Add prizes</a> first.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($drawnCount > 0)
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-red-200 p-5 dark:border-red-900/60">
            <div>
                <h2 class="font-semibold">Reset all draws</h2>
                <p class="text-sm text-stone-600 dark:text-stone-400">Clears every winner so you can draw again, for example after a rehearsal.</p>
            </div>
            <form
                method="POST"
                action="{{ route('admin.draw.destroy') }}"
                onsubmit="if (! confirm('Clear every winner? This cannot be undone.')) { return false; } {{ $disableOnSubmit }}"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-500 disabled:opacity-60"
                >
                    Reset all draws
                </button>
            </form>
        </section>
    @endif
</x-admin-layout>
