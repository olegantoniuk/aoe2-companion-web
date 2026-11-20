/*
 * Home wordmark written stroke by stroke, the way a pen would.
 * The map image holds, per pixel, the moment the pen reaches it (red) and the ink coverage (green).
 * While the pen moves a canvas draws the ink; at the end the crisp SVG takes its place.
 */
(function () {
    var title = document.querySelector('.home-title');
    if (!title) return;
    var svg = title.querySelector('svg');
    var src = title.getAttribute('data-strokes');
    if (!svg || !src || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var DURATION = 4.2;          // seconds for the whole word
    var SOFT = 5;                // width of the wet edge, in map units (0..250)
    var WET = 40;                // fresh ink is a little lighter and darkens over this many units

    title.classList.add('is-writing');

    var map = new Image();
    map.onerror = done;
    map.onload = function () {
        var w = map.naturalWidth, h = map.naturalHeight;
        var read = document.createElement('canvas');
        read.width = w; read.height = h;
        var rctx = read.getContext('2d');
        rctx.drawImage(map, 0, 0);
        var px;
        try { px = rctx.getImageData(0, 0, w, h).data; } catch (e) { done(); return; }

        // only the pixels that carry ink
        var count = 0, i;
        for (i = 0; i < w * h; i++) if (px[i * 4 + 1]) count++;
        var where = new Uint32Array(count), when = new Uint8Array(count), ink = new Uint8Array(count);
        for (i = 0, count = 0; i < w * h; i++) {
            if (px[i * 4 + 1]) { where[count] = i * 4; when[count] = px[i * 4]; ink[count] = px[i * 4 + 1]; count++; }
        }

        var canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.className = 'hero-wordmark-pen';
        canvas.setAttribute('aria-hidden', 'true');
        title.appendChild(canvas);
        var ctx = canvas.getContext('2d');
        var out = ctx.createImageData(w, h);
        var buf = out.data;

        var rgb = (getComputedStyle(title).color.match(/\d+/g) || [27, 27, 27]).map(Number);
        for (i = 0; i < count; i++) {
            var o = where[i];
            buf[o] = rgb[0]; buf[o + 1] = rgb[1]; buf[o + 2] = rgb[2];
        }

        var start = null;
        function frame(now) {
            if (start === null) start = now;
            var p = (now - start) / 1000 / DURATION * 250;
            for (var j = 0; j < count; j++) {
                var age = p - when[j];
                if (age <= 0) { buf[where[j] + 3] = 0; continue; }
                var edge = age >= SOFT ? 1 : age / SOFT;
                var wet = age >= WET ? 1 : 0.72 + 0.28 * age / WET;
                buf[where[j] + 3] = ink[j] * edge * wet;
            }
            ctx.putImageData(out, 0, 0);
            if (p < 250 + WET) requestAnimationFrame(frame);
            else done();
        }
        requestAnimationFrame(frame);
    };
    map.src = src;

    function done() {
        title.classList.remove('is-writing');
        var c = title.querySelector('.hero-wordmark-pen');
        if (c) c.remove();
    }
})();
