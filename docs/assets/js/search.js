document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('menu-search-results');
    if (!searchInput || !searchResults) {
        return;
    }

    let lunrIndex;
    let pages = {};
    let selectedIndex = -1;
    let debounceTimer = null;

    fetch('/search.json')
        .then(response => response.json())
        .then(data => {
            lunrIndex = lunr(function () {
                this.ref('url');
                this.field('title', { boost: 10 });
                this.field('content');
                this.metadataWhitelist = ['position'];

                data.forEach(function (page) {
                    this.add(page);
                    pages[page.url] = page;
                }, this);
            });
        })
        .catch(error => {
            console.error('Error fetching search data or building Lunr index:', error);
        });

    function extractSnippet(page, result) {
        const content = page.content || '';
        let pos = -1;

        if (result.matchData && result.matchData.metadata) {
            for (const term in result.matchData.metadata) {
                const fields = result.matchData.metadata[term];
                if (fields.content && fields.content.position && fields.content.position.length) {
                    pos = fields.content.position[0][0];
                    break;
                }
            }
        }

        const windowSize = 130;
        if (pos === -1) {
            const snippet = content.slice(0, windowSize).trim();
            return snippet + (content.length > windowSize ? '…' : '');
        }

        const start = Math.max(0, pos - 40);
        const end = Math.min(content.length, start + windowSize);
        let snippet = content.slice(start, end).trim();
        if (start > 0) {
            snippet = '…' + snippet;
        }
        if (end < content.length) {
            snippet += '…';
        }
        return snippet;
    }

    function clearResults() {
        searchResults.innerHTML = '';
        searchResults.classList.remove('visible');
        selectedIndex = -1;
    }

    function renderResults(results) {
        selectedIndex = -1;

        if (results.length === 0) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('visible');
            const p = document.createElement('p');
            p.textContent = 'No results found.';
            searchResults.appendChild(p);
            searchResults.classList.add('visible');
            return;
        }

        searchResults.innerHTML = '';
        searchResults.classList.add('visible');
        const ul = document.createElement('ul');

        results.slice(0, 8).forEach(function (result) {
            const page = pages[result.ref];
            if (!page) {
                return;
            }

            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = page.url;

            const titleEl = document.createElement('div');
            titleEl.className = 'pe-search-result__title';
            titleEl.textContent = page.title;
            a.appendChild(titleEl);

            const snippet = extractSnippet(page, result);
            if (snippet) {
                const snippetEl = document.createElement('div');
                snippetEl.className = 'pe-search-result__snippet';
                snippetEl.textContent = snippet;
                a.appendChild(snippetEl);
            }

            li.appendChild(a);
            ul.appendChild(li);
        });

        searchResults.appendChild(ul);
    }

    function performSearch() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            clearResults();
            return;
        }
        if (!lunrIndex) {
            return;
        }
        renderResults(lunrIndex.search(query));
    }

    searchInput.addEventListener('keyup', function (e) {
        if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].indexOf(e.key) !== -1) {
            return;
        }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 150);
    });

    function resultLinks() {
        return Array.prototype.slice.call(searchResults.querySelectorAll('a'));
    }

    function updateSelection(links) {
        links.forEach(function (link, i) {
            link.classList.toggle('pe-search-result--selected', i === selectedIndex);
        });
        if (selectedIndex >= 0 && links[selectedIndex]) {
            links[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    searchInput.addEventListener('keydown', function (e) {
        const links = resultLinks();

        if (e.key === 'ArrowDown') {
            if (links.length === 0) {
                return;
            }
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, links.length - 1);
            updateSelection(links);
        } else if (e.key === 'ArrowUp') {
            if (links.length === 0) {
                return;
            }
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection(links);
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && links[selectedIndex]) {
                e.preventDefault();
                window.location.href = links[selectedIndex].href;
            }
        } else if (e.key === 'Escape') {
            clearResults();
            searchInput.blur();
        }
    });

    // Cmd/Ctrl+K focuses search from anywhere on the page.
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            clearResults();
        }
    });

    const kbdHint = document.querySelector('.pe-search__kbd');
    if (kbdHint && !/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
        kbdHint.textContent = 'Ctrl K';
    }
});
