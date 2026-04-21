// site/assets/js/site.js

// ---------- Booking calculator ----------
(function () {
    const calc = document.getElementById('booking-calc');
    if (!calc) return;
    const price = parseFloat(calc.dataset.price || '0');
    const start = document.querySelector('input[name="start_time"]');
    const end   = document.querySelector('input[name="end_time"]');
    if (!start || !end) return;

    function update() {
        if (!start.value || !end.value) {
            calc.textContent = 'Виберіть час для розрахунку вартості.';
            return;
        }
        const s = new Date(start.value);
        const e = new Date(end.value);
        if (isNaN(s) || isNaN(e) || e <= s) {
            calc.textContent = 'Некоректний інтервал часу.';
            return;
        }
        const hrs = (e - s) / 3600000;
        const total = hrs * price;
        const hrsFmt = hrs % 1 === 0 ? hrs.toFixed(0) : hrs.toFixed(2);
        calc.innerHTML = `Тривалість: <strong>${hrsFmt} год</strong> · ` +
                         `Разом: <strong>${new Intl.NumberFormat('uk-UA', { style: 'currency', currency: 'UAH' }).format(total)}</strong>`;
    }
    start.addEventListener('input', update);
    end.addEventListener('input', update);
    update();
})();

// ---------- Gallery slider ----------
(function () {
    const gallery = document.querySelector('[data-gallery]');
    if (!gallery) return;
    const imgs = gallery.querySelectorAll('.gallery__img');
    const dots = gallery.querySelectorAll('.gallery__dot');
    let idx = 0;

    function show(i) {
        idx = (i + imgs.length) % imgs.length;
        imgs.forEach((im, n) => im.classList.toggle('gallery__img--active', n === idx));
        dots.forEach((d, n) => d.classList.toggle('gallery__dot--active', n === idx));
    }
    gallery.querySelectorAll('.gallery__nav').forEach(btn => {
        btn.addEventListener('click', () => show(idx + parseInt(btn.dataset.dir || '1', 10)));
    });
    dots.forEach(d => d.addEventListener('click', () => show(parseInt(d.dataset.idx, 10))));

    let touchX = 0;
    gallery.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; });
    gallery.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) show(idx + (dx < 0 ? 1 : -1));
    });
})();

// ---------- Main map (home) ----------
(function () {
    const el = document.getElementById('coworkings-map');
    if (!el || typeof L === 'undefined') return;
    let points;
    try { points = JSON.parse(el.dataset.points || '[]'); } catch { points = []; }
    if (points.length === 0) return;

    const map = L.map(el).setView([points[0].latitude, points[0].longitude], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);

    const bounds = [];
    points.forEach(p => {
        if (p.latitude == null || p.longitude == null) return;
        const m = L.marker([p.latitude, p.longitude]).addTo(map);
        m.bindPopup(
            `<strong>${escapeHtml(p.name)}</strong><br>` +
            `<small>${escapeHtml((p.city || '') + ', ' + (p.address || ''))}</small><br>` +
            `<a href="/site/index.php?page=coworking&id=${p.id}">Детальніше →</a>`
        );
        bounds.push([p.latitude, p.longitude]);
    });
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
})();

// ---------- Single coworking map ----------
(function () {
    const el = document.getElementById('cw-map');
    if (!el || typeof L === 'undefined') return;
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    const map = L.map(el).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.name || '').openPopup();
})();
