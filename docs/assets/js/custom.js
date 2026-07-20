document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('mobile-menu-toggle');
    const mainMenu = document.getElementById('main-menu');

    if (toggleButton && mainMenu) {
        toggleButton.addEventListener('click', function () {
            mainMenu.classList.toggle('open');
        });
    }

    initThemeToggle();
    initCodeCopyButtons();
    initHeadingAnchors();
});

function initHeadingAnchors() {
    const container = document.querySelector('.pe-content__container');
    if (!container) {
        return;
    }

    container.querySelectorAll('h2[id], h3[id], h4[id]').forEach(function (heading) {
        const anchor = document.createElement('a');
        anchor.href = '#' + heading.id;
        anchor.className = 'pe-heading-anchor';
        anchor.setAttribute('aria-label', 'Link to this section');
        anchor.textContent = '#';
        heading.appendChild(anchor);
    });
}

function initCodeCopyButtons() {
    const blocks = document.querySelectorAll('.pe-content__container div.highlighter-rouge');

    blocks.forEach(function (block) {
        const codeEl = block.querySelector('pre code');
        if (!codeEl) {
            return;
        }

        const copyIcon =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<rect x="9" y="9" width="13" height="13" rx="2"></rect>' +
            '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>' +
            '</svg>';
        const checkIcon =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<polyline points="20 6 9 17 4 12"></polyline>' +
            '</svg>';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pe-copy-btn';
        button.setAttribute('aria-label', 'Copy code');
        button.innerHTML = copyIcon;

        button.addEventListener('click', function () {
            const text = codeEl.textContent.replace(/\n$/, '');
            if (!navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText(text).then(function () {
                button.innerHTML = checkIcon;
                button.classList.add('pe-copy-btn--copied');
                button.setAttribute('aria-label', 'Copied');
                setTimeout(function () {
                    button.innerHTML = copyIcon;
                    button.classList.remove('pe-copy-btn--copied');
                    button.setAttribute('aria-label', 'Copy code');
                }, 1500);
            });
        });

        block.appendChild(button);
    });
}

function initThemeToggle() {
    const button = document.getElementById('theme-toggle');
    if (!button) {
        return;
    }

    function effectiveTheme() {
        try {
            const stored = localStorage.getItem('pe-theme');
            if (stored === 'light' || stored === 'dark') {
                return stored;
            }
        } catch (e) {}
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }

    button.addEventListener('click', function () {
        const next = effectiveTheme() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try {
            localStorage.setItem('pe-theme', next);
        } catch (e) {}
    });
}
