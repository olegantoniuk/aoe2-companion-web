/*
 * Home hero: waving flags on the castle towers and chimney smoke as a small particle system.
 * Everything is drawn on one canvas laid over the diorama, in the picture's own 1536 x 1024 space.
 */
(function () {
    var canvas = document.querySelector('.hero-fx');
    if (!canvas || !canvas.getContext) return;
    var img = canvas.parentNode.querySelector('img');
    var ctx = canvas.getContext('2d');
    var SPACE_W = 1536;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // sprite sheet rows: where each flag sits in the picture and where its cloth starts
    var FLAGS = [
        { x: 588, y: 36, w: 88, h: 59, sy: 0, cloth: 13, phase: 0.0, amp: 3.2 },
        { x: 815, y: 0, w: 85, h: 44, sy: 61, cloth: 12, phase: 1.7, amp: 2.8 },
        { x: 950, y: 70, w: 74, h: 42, sy: 107, cloth: 10, phase: 3.1, amp: 2.6 }
    ];
    // chimney tops the smoke rises from
    var CHIMNEYS = [
        { x: 192, y: 318, rate: 4 },
        { x: 293, y: 271, rate: 3.5 }
    ];

    var sheet = new Image();
    sheet.src = canvas.getAttribute('data-flags');

    // one offscreen canvas per flag, at sprite resolution, so the bent columns never leave seams
    FLAGS.forEach(function (f) {
        f.buf = document.createElement('canvas');
        f.buf.width = f.w;
        f.buf.height = f.h + 12;
        f.bctx = f.buf.getContext('2d');
    });

    // soft smoke puff sprite: a few overlapping blurred blobs
    var puff = document.createElement('canvas');
    puff.width = puff.height = 64;
    (function () {
        var p = puff.getContext('2d');
        [[32, 32, 26, 0.8], [22, 36, 16, 0.55], [42, 28, 17, 0.55], [34, 22, 14, 0.45], [30, 42, 13, 0.4]].forEach(function (b) {
            var g = p.createRadialGradient(b[0], b[1], 0, b[0], b[1], b[2]);
            g.addColorStop(0, 'rgba(226, 223, 218,' + b[3] + ')');
            g.addColorStop(0.6, 'rgba(206, 202, 196,' + b[3] * 0.55 + ')');
            g.addColorStop(1, 'rgba(210, 206, 200, 0)');
            p.fillStyle = g;
            p.beginPath();
            p.arc(b[0], b[1], b[2], 0, Math.PI * 2);
            p.fill();
        });
    })();

    var particles = [];
    var scale = 1;
    var dpr = 1;

    function resize() {
        var w = img.clientWidth, h = img.clientHeight;
        if (!w || !h) return;
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        scale = (w / SPACE_W) * dpr;
    }

    function drawFlag(f, t) {
        var b = f.bctx;
        b.clearRect(0, 0, f.buf.width, f.buf.height);
        var span = f.w - f.cloth;
        for (var c = 0; c < f.w; c++) {
            var off = 0, slope = 0;
            if (c >= f.cloth) {
                var k = (c - f.cloth) / span;              // 0 at the pole, 1 at the tip
                var a = f.amp * Math.pow(k, 1.2);
                var arg = t * 5.2 - (c - f.cloth) * 0.16 + f.phase;
                off = a * Math.sin(arg) + a * 0.35 * Math.sin(arg * 1.9 + 1.3);
                slope = Math.cos(arg) * k;
            }
            b.drawImage(sheet, c, f.sy, 1, f.h, c, 6 + off, 1, f.h);
            if (slope) {
                // light and shade on the folds
                b.globalCompositeOperation = 'source-atop';
                b.fillStyle = slope > 0 ? 'rgba(255,240,230,' + (slope * 0.16) + ')' : 'rgba(40,0,0,' + (-slope * 0.22) + ')';
                b.fillRect(c, 0, 1, f.buf.height);
                b.globalCompositeOperation = 'source-over';
            }
        }
        ctx.drawImage(f.buf, f.x * scale, (f.y - 6) * scale, f.w * scale, f.buf.height * scale);
    }

    function emit(ch) {
        particles.push({
            x: ch.x + (Math.random() - 0.5) * 4,
            y: ch.y,
            vx: 7 + Math.random() * 6,
            vy: -(20 + Math.random() * 9),
            age: 0,
            life: 3.2 + Math.random() * 1.6,
            size: 16 + Math.random() * 8,
            grow: 13 + Math.random() * 7,
            rot: Math.random() * Math.PI * 2,
            spin: (Math.random() - 0.5) * 0.6,
            wob: Math.random() * Math.PI * 2
        });
    }

    function drawSmoke(dt) {
        CHIMNEYS.forEach(function (ch) {
            ch.acc = (ch.acc || 0) + dt * ch.rate;
            while (ch.acc >= 1) { emit(ch); ch.acc -= 1; }
        });
        for (var i = particles.length - 1; i >= 0; i--) {
            var p = particles[i];
            p.age += dt;
            if (p.age >= p.life) { particles.splice(i, 1); continue; }
            var u = p.age / p.life;
            p.x += (p.vx + Math.sin(p.age * 1.8 + p.wob) * 3) * dt;
            p.y += p.vy * dt;
            p.vy *= 1 - 0.18 * dt;                       // slows as it cools
            p.rot += p.spin * dt;
            var size = p.size + p.grow * p.age;
            var alpha = Math.min(1, u * 5) * Math.pow(1 - u, 1.3);
            ctx.save();
            ctx.globalAlpha = alpha;
            ctx.translate(p.x * scale, p.y * scale);
            ctx.rotate(p.rot);
            ctx.drawImage(puff, -size * scale / 2, -size * scale / 2, size * scale, size * scale);
            ctx.restore();
        }
    }

    var last = 0, running = false, visible = true;

    function frame(now) {
        if (!running) return;
        var t = now / 1000;
        var dt = last ? Math.min(0.05, t - last) : 0.016;
        last = t;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawSmoke(dt);
        FLAGS.forEach(function (f) { drawFlag(f, t); });
        requestAnimationFrame(frame);
    }

    function start() {
        if (running || reduce || !visible || document.hidden) return;
        running = true;
        last = 0;
        requestAnimationFrame(frame);
    }

    function stop() { running = false; }

    function still() {
        // reduced motion: the flags still have to be there, just not moving
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        FLAGS.forEach(function (f) { drawFlag(f, 0); });
    }

    function ready() {
        resize();
        if (reduce) { still(); return; }
        // pre-warm the smoke so the columns are already up when the page opens
        for (var i = 0; i < 120; i++) {
            CHIMNEYS.forEach(function (ch) {
                ch.acc = (ch.acc || 0) + 0.033 * ch.rate;
                while (ch.acc >= 1) { emit(ch); ch.acc -= 1; }
            });
            particles.forEach(function (p) { p.age += 0.033; p.x += p.vx * 0.033; p.y += p.vy * 0.033; p.vy *= 1 - 0.18 * 0.033; });
            particles = particles.filter(function (p) { return p.age < p.life; });
        }
        start();
    }

    var pending = 2;
    function loaded() { if (--pending === 0) ready(); }
    if (sheet.complete && sheet.naturalWidth) loaded(); else sheet.onload = loaded;
    if (img.complete && img.naturalWidth) loaded(); else img.addEventListener('load', loaded);

    window.addEventListener('resize', function () { resize(); if (reduce) still(); });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            visible = entries[0].isIntersecting;
            visible ? start() : stop();
        }).observe(canvas);
    }
})();
