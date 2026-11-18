@props(['name', 'label', 'hint' => null])

@php
    $hasError = $errors->has($name);
    $describedBy = collect([
        $hint ? "{$name}-hint" : null,
        $hasError ? "{$name}-error" : null,
    ])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-2 kiosk:gap-4">
    <label for="{{ $name }}" class="text-lg font-medium text-white/90 kiosk:text-3xl">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="text"
        value="{{ old($name) }}"
        autocomplete="off"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class([
            'w-full rounded-2xl border bg-[#0b1a3a]/70 px-5 py-4 text-xl text-white caret-amber-300 shadow-inner outline-none transition placeholder:text-white/30 focus:bg-[#0b1a3a]/90 focus:ring-4 kiosk:rounded-3xl kiosk:border-2 kiosk:px-8 kiosk:py-7 kiosk:text-4xl kiosk:focus:ring-8',
            'border-white/15 focus:border-amber-300 focus:ring-amber-300/25' => ! $hasError,
            'border-rose-400 focus:border-rose-400 focus:ring-rose-400/25' => $hasError,
        ]) }}
    >

    @if ($hint)
        <p id="{{ $name }}-hint" class="text-sm text-white/50 kiosk:text-xl">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="text-base font-medium text-rose-300 kiosk:text-2xl">{{ $message }}</p>
    @enderror
</div>
