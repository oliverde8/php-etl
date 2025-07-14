document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('menu-search-results');
    let lunrIndex;
    let pages = {};

    // Fetch the search index and build Lunr index
    fetch('/search.json')
        .then(response => response.json())
        .then(data => {
            lunrIndex = lunr(function () {
                this.ref('url');
                this.field('title', { boost: 10 });
                this.field('content');

                data.forEach(function (page) {
                    this.add(page);
                    pages[page.url] = page; // Store page data for displaying results
                }, this);
            });
        })
        .catch(error => {
            console.error('Error fetching search data or building Lunr index:', error);
        });

    searchInput.addEventListener('keyup', function() {
        const query = searchInput.value;
        searchResults.innerHTML = ''; // Clear previous results

        if (query.length < 2) { // Require at least 2 characters for search
            return;
        }

        const results = lunrIndex.search(query); // Perform search

        console.log(results);
        if (results.length > 0) {
            searchResults.classList.add('visible');
            const ul = document.createElement('ul');
            results.forEach(result => {
                const page = pages[result.ref];
                if (page) {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = page.url;
                    a.textContent = page.title;
                    li.appendChild(a);
                    ul.appendChild(li);
                }
            });
            searchResults.appendChild(ul);
        } else {
            searchResults.classList.remove('visible');
            const p = document.createElement('p');
            p.textContent = 'No results found.';
            searchResults.appendChild(p);
        }
    });
});