// Small enhancements for every page. No library needed, and no inline scripts anywhere:
// the Content-Security-Policy only allows files from this website.

document.documentElement.classList.add('js');   // lets the css know that javascript works (menu button on phones)

document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});

// For javascript that sends requests to your controllers: the csrf token has to travel along with everything
// that changes something. Put <meta name="csrf-token" content="<?= e(Csrf::token()) ?>"> into a view that needs it, then:
//   fetch('/shop/add', { method: 'POST', headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content }, body: ... })
