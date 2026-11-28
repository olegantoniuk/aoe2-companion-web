/*
 * Live filters: a form[data-live-filter="<selector>"] filters the [data-filter-item] elements inside that selector
 * while the reader types or picks, with no submit button.
 *   - the field "q" searches the item's data-q (its name in every language), every word must match;
 *   - any other field matches the item's data attribute of the same name (select name="age" -> data-age);
 *   - a [data-filter-group] with nothing left hides, its [data-group-count] shows how many are left,
 *     and an element with data-filter-for="<group id>" (a jump chip) hides with it;
 *   - [data-live-count] shows the total left, [data-live-empty] shows when nothing matches;
 *   - table rows get .is-stripe on every other visible row (the table has .live-striped instead of .table-striped);
 *   - the URL keeps the filters, so a filtered list can be shared and the back button restores it.
 */
(function () {
    function norm(s) {
        return String(s || '').toLowerCase().replace(/[’ʼ`]/g, "'").replace(/ё/g, 'е').replace(/\s+/g, ' ').trim();
    }

    document.querySelectorAll('form[data-live-filter]').forEach(function (form) {
        var root = document.querySelector(form.getAttribute('data-live-filter')) || document;
        var items = [].slice.call(root.querySelectorAll('[data-filter-item]'));
        var groups = [].slice.call(root.querySelectorAll('[data-filter-group]'));
        var counts = [].slice.call(document.querySelectorAll('[data-live-count]'));
        var empties = [].slice.call(document.querySelectorAll('[data-live-empty]'));
        var spinner = form.querySelector('.live-filter-spinner');
        var fields = [].slice.call(form.elements).filter(function (el) { return el.name; });
        var timer = null;

        items.forEach(function (it) { it._q = norm(it.getAttribute('data-q')); });

        function apply() {
            var words = [];
            var want = {};
            fields.forEach(function (f) {
                if (f.name === 'q') words = norm(f.value).split(' ').filter(Boolean);
                else if (f.value !== '') want[f.name] = f.value;
            });

            var total = 0;
            var stripe = 0;
            items.forEach(function (it) {
                var ok = words.every(function (w) { return it._q.indexOf(w) !== -1; });
                for (var k in want) {
                    if (ok && it.getAttribute('data-' + k) !== want[k]) ok = false;
                }
                it.hidden = !ok;
                if (ok) {
                    total++;
                    if (it.tagName === 'TR') it.classList.toggle('is-stripe', stripe++ % 2 === 0);
                }
            });

            groups.forEach(function (g) {
                var left = [].filter.call(g.querySelectorAll('[data-filter-item]'), function (it) { return !it.hidden; }).length;
                g.hidden = left === 0;
                g.querySelectorAll('[data-group-count]').forEach(function (c) { c.textContent = left; });
                if (g.id) {
                    document.querySelectorAll('[data-filter-for="' + g.id + '"]').forEach(function (chip) {
                        chip.hidden = left === 0;
                        var small = chip.querySelector('small');
                        if (small) small.textContent = left;
                    });
                }
            });
            counts.forEach(function (c) { c.textContent = total; });
            empties.forEach(function (e) { e.hidden = total > 0; });

            var params = new URLSearchParams(location.search);
            fields.forEach(function (f) {
                if (f.value.trim() !== '') params.set(f.name, f.value.trim());
                else params.delete(f.name);
            });
            var qs = params.toString();
            history.replaceState(history.state, '', location.pathname + (qs ? '?' + qs : '') + location.hash);
        }

        var search = form.querySelector('input[name="q"]');
        var clear = form.querySelector('.live-filter-clear');

        // the cross that empties the search shows while there is text and the spinner is not in its place
        function toggleClear(busy) {
            if (clear) clear.hidden = busy || !search || search.value === '';
        }

        // a short pause while typing, so the list does not flicker on every key; the spinner shows it is working
        function schedule(delay) {
            if (spinner) spinner.hidden = false;
            toggleClear(true);
            clearTimeout(timer);
            timer = setTimeout(function () {
                apply();
                if (spinner) spinner.hidden = true;
                toggleClear(false);
            }, delay);
        }

        if (clear && search) {
            clear.addEventListener('click', function () {
                search.value = '';
                search.focus();
                schedule(0);
            });
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && search.value !== '') {
                    search.value = '';
                    schedule(0);
                }
            });
        }

        fields.forEach(function (f) {
            f.addEventListener(f.tagName === 'SELECT' ? 'change' : 'input', function () {
                schedule(f.tagName === 'SELECT' ? 120 : 250);
            });
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            schedule(0);
        });
        form.querySelectorAll('[data-live-reset]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                fields.forEach(function (f) { f.value = ''; });
                schedule(0);
            });
        });

        apply();
        toggleClear(false);
    });
})();
