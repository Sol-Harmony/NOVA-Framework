// Small enhancements for every page. No library needed, and no inline scripts anywhere:
// the Content-Security-Policy only allows files from this website.

document.documentElement.classList.add('js');   // lets the css know that javascript works (menu button on phones)

document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    initLightbox();

    // text blocks (class "reveal") rise into place when they scroll into view, the css does the animation
    var blocks = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
        blocks.forEach(function (block) { block.classList.add('in-view'); });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    blocks.forEach(function (block) { observer.observe(block); });
});

// Picture viewer for the gallery: a click on a picture shows it big above the page, with arrows (or the arrow keys, or a swipe)
// to switch pictures and Esc (or a click next to the picture) to close it.
function initLightbox() {
    var gallery = document.querySelector('.gallery');
    if (!gallery) {
        return;
    }
    var links = Array.prototype.slice.call(gallery.querySelectorAll('a'));
    if (!links.length) {
        return;
    }

    function button(className, label, symbol) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'lightbox-btn ' + className;
        b.setAttribute('aria-label', label);
        b.textContent = symbol;
        return b;
    }

    var box = document.createElement('div');
    box.className = 'lightbox';
    box.hidden = true;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    var img = document.createElement('img');
    img.className = 'lightbox-img';
    var count = document.createElement('div');
    count.className = 'lightbox-count';
    var closeBtn = button('lightbox-close', gallery.dataset.labelClose || 'Close', '×');
    var prevBtn = button('lightbox-prev', gallery.dataset.labelPrev || 'Previous', '‹');
    var nextBtn = button('lightbox-next', gallery.dataset.labelNext || 'Next', '›');
    box.append(img, count, closeBtn, prevBtn, nextBtn);
    document.body.appendChild(box);

    var current = 0;
    var opener = null;

    function show(index) {
        current = (index + links.length) % links.length;
        var thumb = links[current].querySelector('img');
        img.src = links[current].getAttribute('href');
        img.alt = thumb ? thumb.alt : '';
        count.textContent = (current + 1) + ' / ' + links.length;
        box.classList.toggle('single', links.length < 2);
        // load the neighbours already, so the next click shows the picture right away
        [current - 1, current + 1].forEach(function (i) {
            new Image().src = links[(i + links.length) % links.length].getAttribute('href');
        });
    }

    function open(index) {
        opener = document.activeElement;
        show(index);
        box.hidden = false;
        document.body.classList.add('lightbox-open');
        requestAnimationFrame(function () { box.classList.add('visible'); });
        closeBtn.focus();
    }

    function close() {
        box.classList.remove('visible');
        box.hidden = true;
        img.removeAttribute('src');
        document.body.classList.remove('lightbox-open');
        if (opener && opener.focus) {
            opener.focus();
        }
    }

    links.forEach(function (link, i) {
        link.addEventListener('click', function (event) {
            // ctrl/cmd/shift-click keeps the normal browser behaviour (new tab)
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            open(i);
        });
    });

    prevBtn.addEventListener('click', function () { show(current - 1); });
    nextBtn.addEventListener('click', function () { show(current + 1); });
    closeBtn.addEventListener('click', close);
    box.addEventListener('click', function (event) {
        if (event.target === box) {     // the dark area around the picture
            close();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (box.hidden) {
            return;
        }
        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'ArrowLeft') {
            show(current - 1);
        } else if (event.key === 'ArrowRight') {
            show(current + 1);
        } else if (event.key === 'Tab') {
            // keep the focus inside the viewer
            var buttons = [closeBtn, prevBtn, nextBtn].filter(function (b) { return b.offsetParent !== null; });
            var first = buttons[0];
            var last = buttons[buttons.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // swipe on phones
    var startX = null;
    box.addEventListener('touchstart', function (event) { startX = event.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (event) {
        if (startX === null) {
            return;
        }
        var dx = event.changedTouches[0].clientX - startX;
        startX = null;
        if (Math.abs(dx) > 50) {
            show(current + (dx < 0 ? 1 : -1));
        }
    }, { passive: true });
}

// For javascript that sends requests to your controllers: the csrf token has to travel along with everything
// that changes something. Put <meta name="csrf-token" content="<?= e(Csrf::token()) ?>"> into a view that needs it, then:
//   fetch('/shop/add', { method: 'POST', headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content }, body: ... })
