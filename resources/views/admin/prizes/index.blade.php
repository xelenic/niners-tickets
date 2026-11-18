@php
    $iconButtonClasses = 'inline-flex size-9 items-center justify-center rounded-lg border border-stone-300 text-stone-700 transition hover:bg-stone-100 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800';
@endphp

<x-admin-layout title="Prizes">
    <div>
        <h1 class="text-3xl font-semibold tracking-tight">Prizes</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-400">
            {{ $prizes->count() }} {{ Str::plural('prize', $prizes->count()) }}, drawn from the lowest to the highest. The last prize is the grand prize.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('admin.prizes.store') }}"
        class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
    >
        @csrf

        <label for="name" class="text-sm font-medium">Add a prize</label>
        <div class="flex flex-col gap-2 sm:flex-row">
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                maxlength="255"
                required
                placeholder="e.g. Dinner for two at Cinnamon Grand, Colombo"
                aria-describedby="name-hint @error('name') name-error @enderror"
                @error('name') aria-invalid="true" @enderror
                @class([
                    'w-full rounded-xl border bg-white px-4 py-2.5 outline-none focus:ring-4 dark:bg-stone-950',
                    'border-stone-300 focus:border-amber-500 focus:ring-amber-500/20 dark:border-stone-700' => ! $errors->has('name'),
                    'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('name'),
                ])
            >
            <button
                type="submit"
                class="shrink-0 rounded-xl bg-stone-900 px-5 py-2.5 font-medium text-white transition hover:bg-stone-700 dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
            >
                Add prize
            </button>
        </div>
        <p id="name-hint" class="text-xs text-stone-500 dark:text-stone-400">New prizes go to the end of the list. Use the arrows to move them.</p>
        @error('name')
            <p id="name-error" class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </form>

    <div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-100 text-xs tracking-wide text-stone-600 uppercase dark:border-stone-800 dark:bg-stone-800/50 dark:text-stone-400">
                <tr>
                    <th scope="col" class="w-14 px-4 py-3 font-semibold">#</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Prize</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                @forelse ($prizes as $prize)
                    <tr>
                        <td class="px-4 py-3 text-stone-500 tabular-nums dark:text-stone-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $prize->name }}</span>
                                @if ($loop->last)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Grand prize</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <form method="POST" action="{{ route('admin.prizes.position.update', $prize) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="{{ $iconButtonClasses }}" title="Move up" aria-label="Move {{ $prize->name }} up" @disabled($loop->first)>
                                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5-5 5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.prizes.position.update', $prize) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="{{ $iconButtonClasses }}" title="Move down" aria-label="Move {{ $prize->name }} down" @disabled($loop->last)>
                                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    </button>
                                </form>
                                <a
                                    href="{{ route('admin.prizes.edit', $prize) }}"
                                    class="ml-2 rounded-lg px-3 py-2 font-medium text-stone-700 transition hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800"
                                >
                                    Edit
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('admin.prizes.destroy', $prize) }}"
                                    onsubmit="return confirm({{ Js::from('Delete “'.$prize->name.'”? This cannot be undone.') }})"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="rounded-lg px-3 py-2 font-medium text-red-700 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center text-stone-500 dark:text-stone-400">No prizes yet. Add the first one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
