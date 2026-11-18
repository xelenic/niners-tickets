<x-layout title="Admin log in">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="flex w-full max-w-sm flex-col gap-8">
            <div class="text-center">
                <p class="text-sm font-semibold tracking-widest text-amber-700 uppercase dark:text-amber-400">
                    {{ config('raffle.name') }}
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">Admin log in</h1>
            </div>

            <form
                method="POST"
                action="{{ route('admin.login.store') }}"
                class="flex flex-col gap-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-xs sm:p-8 dark:border-stone-800 dark:bg-stone-900"
            >
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        autofocus
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        @class([
                            'w-full rounded-xl border bg-white px-4 py-2.5 outline-none focus:ring-4 dark:bg-stone-950',
                            'border-stone-300 focus:border-amber-500 focus:ring-amber-500/20 dark:border-stone-700' => ! $errors->has('email'),
                            'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('email'),
                        ])
                    >
                    @error('email')
                        <p id="email-error" class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        @class([
                            'w-full rounded-xl border bg-white px-4 py-2.5 outline-none focus:ring-4 dark:bg-stone-950',
                            'border-stone-300 focus:border-amber-500 focus:ring-amber-500/20 dark:border-stone-700' => ! $errors->has('password'),
                            'border-red-500 focus:border-red-500 focus:ring-red-500/20' => $errors->has('password'),
                        ])
                    >
                    @error('password')
                        <p id="password-error" class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-400">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded accent-amber-500">
                    Keep me logged in
                </label>

                <button
                    type="submit"
                    class="rounded-xl bg-stone-900 px-5 py-3 font-semibold text-white transition hover:bg-stone-700 focus:ring-4 focus:ring-stone-900/20 focus:outline-none dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
                >
                    Log in
                </button>
            </form>
        </div>
    </main>
</x-layout>
