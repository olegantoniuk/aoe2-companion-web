/*
 * Build order scenes: fit each scene to the column and centre what is drawn.
 * A scene that is a little too wide is drawn smaller; one that would shrink a lot wraps onto a second row
 * instead, so the sprites stay at a readable size. Then the scene gets a height that fits its drawing,
 * and the drawing is moved so it sits in the middle of the grass.
 */
(function () {
    var list = document.querySelector('.build-scenes');
    if (!list) return;
    var scenes = [].slice.call(list.querySelectorAll('.scene'));
    var MIN_H = 150;      // px, the shortest scene
    var PAD_Y = 18;       // px of grass above and below the drawing
    var WRAP_BELOW = 0.82; // shrinking more than this wraps the scene instead

    function fit(scene) {
        var inner = scene.querySelector('.scene-inner');
        var cs = getComputedStyle(scene);
        var avail = scene.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        if (!inner || avail <= 0) return;
        inner.style.zoom = '';
        inner.style.top = '';
        inner.classList.remove('is-wrapped');

        var z = 1;
        var natural = inner.scrollWidth;
        if (natural > avail + 1) {
            z = avail / natural;
            if (z < WRAP_BELOW) {
                inner.classList.add('is-wrapped');
                var widest = 0;
                [].forEach.call(inner.children, function (c) { widest = Math.max(widest, c.getBoundingClientRect().width); });
                z = Math.min(1, avail / widest);
                inner.style.zoom = z;
                // two rows at most: shrink a little more rather than stack a third row
                while (rows(inner) > 2 && z > 0.7) {
                    z -= 0.05;
                    inner.style.zoom = z;
                }
            }
        }
        if (z < 1) inner.style.zoom = z;

        // the drawn box: sprites, badges and counts
        var top = Infinity, bottom = -Infinity;
        [].forEach.call(inner.querySelectorAll('img, .scene-badge, .scene-count'), function (el) {
            var r = el.getBoundingClientRect();
            if (!r.height) return;
            top = Math.min(top, r.top);
            bottom = Math.max(bottom, r.bottom);
        });
        if (top === Infinity) return;
        var h = bottom - top;
        var H = Math.max(MIN_H, Math.ceil(h + 2 * PAD_Y));
        scene.style.height = H + 'px';
        var shift = (H - h) / 2 - (top - scene.getBoundingClientRect().top);
        // lengths inside a zoomed element are in its own, unzoomed pixels
        inner.style.top = (shift / z) + 'px';
    }

    function rows(inner) {
        // items in one row share the ground line, so they end at the same height
        var lines = [];
        [].forEach.call(inner.children, function (c) {
            var y = c.offsetTop + c.offsetHeight;
            if (!lines.some(function (l) { return Math.abs(l - y) < 4; })) lines.push(y);
        });
        return lines.length;
    }

    function fitAll() {
        if (!list.offsetWidth) return;
        scenes.forEach(fit);
    }

    // runs when the Visual tab opens (the list goes from no width to its width) and on every width change
    var lastWidth = 0;
    if ('ResizeObserver' in window) {
        new ResizeObserver(function () {
            if (list.offsetWidth === lastWidth) return;
            lastWidth = list.offsetWidth;
            fitAll();
        }).observe(list);
    } else {
        window.addEventListener('resize', fitAll);
        document.addEventListener('shown.bs.tab', fitAll);
    }

    // unit sprites have no size until they load
    scenes.forEach(function (scene) {
        [].forEach.call(scene.querySelectorAll('img'), function (img) {
            if (!img.complete) img.addEventListener('load', function () { if (list.offsetWidth) fit(scene); });
        });
    });
    fitAll();
})();
