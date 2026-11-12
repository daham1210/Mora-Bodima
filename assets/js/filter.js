document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('searchFilterForm');
    const resultsContainer = document.getElementById('searchResults');

    if (!filterForm || !resultsContainer) return;

    const loadListings = async () => {
        const params = new URLSearchParams(new FormData(filterForm)).toString();
        const endpoint = filterForm.getAttribute('data-endpoint') + '?' + params;

        try {
            const res = await fetch(endpoint);
            const html = await res.text();
            resultsContainer.innerHTML = html;
        } catch (err) {
            resultsContainer.innerHTML = '<p class="alert alert-error">Failed to load boarding houses.</p>';
        }
    };

    filterForm.addEventListener('input', loadListings);
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        loadListings();
    });

    loadListings();
});