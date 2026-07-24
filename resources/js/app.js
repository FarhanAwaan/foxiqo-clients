import './bootstrap';
import '@tabler/core/dist/js/tabler.esm.js';

// Deliberately NOT importing the standalone `bootstrap` package here.
// tabler.esm.js already bundles its own copy of Bootstrap's JS internally;
// importing a second copy registers a duplicate set of global data-api
// listeners (dropdown, etc.) that fight the first and silently break
// toggling. public/js/custom.js guards its `bootstrap.Tooltip` call for
// the one case (login page "show password" tooltip) where the global
// isn't present.

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle-btn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
    });
});

// Page loader — present in the initial HTML (so there's no flash of real
// content first) and hidden after a minimum duration. The staggered card
// entrance is deliberately triggered at that same moment rather than on
// DOMContentLoaded: the old code applied it immediately, so the ~500ms
// animation had already finished before the page was even visible to
// look at. Triggering it as the loader fades makes it the reveal moment.
(function () {
    var MIN_LOADER_MS = 1800;
    var startedAt = Date.now();

    function revealPage() {
        var elapsed = Date.now() - startedAt;
        var remaining = Math.max(0, MIN_LOADER_MS - elapsed);

        setTimeout(function () {
            var loader = document.getElementById('page-loader');
            if (loader) loader.classList.add('is-hidden');

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            var index = 0;
            document.querySelectorAll('.page-body .card').forEach(function (card) {
                if (card.parentElement.closest('.card, .modal-content, .offcanvas-body')) return;
                card.classList.add('stagger-in');
                card.style.animationDelay = (Math.min(index, 6) * 40) + 'ms';
                index++;
            });
        }, remaining);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', revealPage);
    } else {
        revealPage();
    }
})();
