<!-- Master data unit selector (master_units). Posts the unit ID and shows the unit code (e.g. KGM).
     "selected" may be a unit ID or a legacy text code (e.g. "LTR" typed before this change); both pre-select.  -->
@props(['name', 'units', 'selected' => null])

@php
    $current = trim((string) $selected);
    $selectedId = $current === ''
        ? null
        : $units->first(fn ($u) => (string) $u->id === $current || strcasecmp((string) $u->unit, $current) === 0)?->id;
@endphp

<select name="{{ $name }}" data-searchable
    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
    <option value="">Select Unit...</option>
    @foreach ($units as $u)
        <option value="{{ $u->id }}" {{ $selectedId !== null && (string) $selectedId === (string) $u->id ? 'selected' : '' }}>{{ $u->unit }}</option>
    @endforeach
</select>
@error($name)
    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
@enderror
