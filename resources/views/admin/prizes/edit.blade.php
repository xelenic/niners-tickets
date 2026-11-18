<x-admin-layout title="Edit prize">
    <div>
        <a
            href="{{ route('admin.prizes.index') }}"
            class="text-sm font-medium text-stone-600 underline-offset-4 hover:text-stone-900 hover:underline dark:text-stone-400 dark:hover:text-white"
        >
            ← Back to prizes
        </a>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Edit prize</h1>
    </div>

    <form
        method="POST"
        action="{{ route('admin.prizes.update', $prize) }}"
        class="flex max-w-xl flex-col gap-5 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900"
    >
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-1.5">
            <label for="name" class="text-sm font-medium">Prize name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $prize->name) }}"
                maxlength="255"
                required
                autofocus
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                @class([
                    'w-full rounded-xl border bg-white px-4 py-2.5 outline-none focus:ring-4 dark:bg-stone-950',
                    'border-stone-300 focus:border-amber-500 focus:ring-amber-500/20 dark:border-stone-700' => ! $errors->has('name'),
                    'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('name'),
                ])
            >
            @error('name')
                <p id="name-error" class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-xl bg-stone-900 px-5 py-2.5 font-medium text-white transition hover:bg-stone-700 dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
            >
                Save changes
            </button>
            <a
                href="{{ route('admin.prizes.index') }}"
                class="rounded-xl px-4 py-2.5 font-medium text-stone-600 transition hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
            >
                Cancel
            </a>
        </div>
    </form>
</x-admin-layout>
