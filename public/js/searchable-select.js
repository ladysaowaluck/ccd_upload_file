/*
 * Type-to-search dropdowns.
 * Any <select data-searchable> becomes a Tom Select box (self-hosted in public/vendor/tom-select):
 * type to filter, arrow keys to move, Enter (or Tab) to pick. The original <select> stays in the
 * form, so the same value (e.g. the master data ID) is posted and old() / required still work.
 */
(function () {
    function placeholderOf(select) {
        var first = select.querySelector('option[value=""]');
        return first ? first.textContent.trim() : 'Type to search...';
    }

    function enhance(select) {
        if (select.tomselect || typeof window.TomSelect === 'undefined') {
            return;
        }

        new window.TomSelect(select, {
            create: false,
            // The empty option ("Select Customer...") is shown as the placeholder, not as a pickable row,
            // so typing filters straight away. Optional fields get an "x" button to clear the value.
            plugins: select.required ? [] : ['clear_button'],
            maxOptions: null,          // show every option, not only the first 50
            selectOnTab: true,
            placeholder: placeholderOf(select),
            searchField: ['text'],
            render: {
                no_results: function () {
                    return '<div class="no-results">No match found</div>';
                }
            }
        });
    }

    function enhanceAll(root) {
        (root || document).querySelectorAll('select[data-searchable]').forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceAll(document);
    });

    window.enhanceSearchableSelects = enhanceAll;
})();
