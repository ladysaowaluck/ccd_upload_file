<!-- Shows the validation error for one input, directly below it.
     When "form" is given, the error is shown only if that form (modal id) was the one submitted,
     so the same field name in other modals on the page does not show the message.  -->
@props(['field', 'form' => null])

@if ($form === null || old('_form') === $form)
    @error($field)
        <p class="mt-1 flex items-start gap-1 text-[11px] font-semibold text-rose-600" role="alert">
            <svg class="w-3.5 h-3.5 mt-px flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
@endif
