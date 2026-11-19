 <!-- After a failed submit (e.g. duplicate value) the page reloads with errors.
     Re-open the modal that was submitted (its id is posted as hidden "_form") so the user
     sees the field error and the values they typed. -->
@if ($errors->any() && is_string(old('_form')))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById(@json(old('_form')));
            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    </script>
@endif
