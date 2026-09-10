/* Admin editor interactions: dynamic spec rows + image removal toggles. */
(function () {
    'use strict';

    // ---- Spec rows: add / remove -----------------------------------------
    var specRows = document.getElementById('specRows');
    var addSpec = document.getElementById('addSpec');

    function makeSpecRow() {
        var row = document.createElement('div');
        row.className = 'spec-row';
        row.innerHTML =
            '<input type="text" name="spec_label[]" placeholder="Label (e.g. CPU)">' +
            '<input type="text" name="spec_value[]" placeholder="Value (e.g. M3 Pro)">' +
            '<button type="button" class="icon-btn" data-remove-spec title="Remove row">×</button>';
        return row;
    }

    if (addSpec && specRows) {
        addSpec.addEventListener('click', function () {
            specRows.appendChild(makeSpecRow());
        });
    }

    if (specRows) {
        specRows.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-remove-spec]');
            if (!btn) return;
            var rows = specRows.querySelectorAll('.spec-row');
            if (rows.length > 1) {
                btn.closest('.spec-row').remove();
            } else {
                // Keep at least one empty row — just clear it.
                var inputs = btn.closest('.spec-row').querySelectorAll('input');
                inputs.forEach(function (i) { i.value = ''; });
            }
        });
    }

    // ---- Image removal toggles -------------------------------------------
    document.querySelectorAll('[data-toggle-remove]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('[data-img]');
            var input = item.querySelector('[data-remove-input]');
            var filename = input.getAttribute('data-filename');
            var marked = item.classList.toggle('is-removing');
            input.value = marked ? filename : '';
            btn.textContent = marked ? '↺' : '×'; // ↺ to undo, × to mark
            btn.title = marked ? 'Undo removal' : 'Mark for removal';
        });
    });
})();
