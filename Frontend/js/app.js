document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.getElementById('navLinks');
    if (navLinks) {
        fetch('../backend/navbar.php')
            .then(res => res.text())
            .then(html => { navLinks.innerHTML = html; });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        if (urlParams.get('msg')) {
            alertBox.innerHTML = `<div class="alert alert-success">${urlParams.get('msg')}</div>`;
        } else if (urlParams.get('err')) {
            alertBox.innerHTML = `<div class="alert alert-error">${urlParams.get('err')}</div>`;
        }
    }

    const filterForm = document.getElementById('searchFilterForm');
    const resultsContainer = document.getElementById('searchResults');
    if (filterForm && resultsContainer) {
        function loadHouses() {
            const params = new URLSearchParams(new FormData(filterForm)).toString();
            fetch('../backend/get_houses.php?' + params)
                .then(res => res.text())
                .then(html => { resultsContainer.innerHTML = html; });
        }
        filterForm.addEventListener('input', loadHouses);
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            loadHouses();
        });
        loadHouses();
    }

    const houseDetailsContainer = document.getElementById('houseDetailsContainer');
    if (houseDetailsContainer) {
        const houseId = urlParams.get('id') || 0;
        fetch('../backend/view_house_data.php?id=' + houseId)
            .then(res => res.text())
            .then(html => { houseDetailsContainer.innerHTML = html; });
    }

    const editHouseContainer = document.getElementById('editHouseContainer');
    if (editHouseContainer) {
        const houseId = urlParams.get('id') || 0;
        fetch('../backend/edit_house_process.php?id=' + houseId)
            .then(res => res.text())
            .then(html => { editHouseContainer.innerHTML = html; });
    }

    const adminContainer = document.getElementById('adminContainer');
    if (adminContainer) {
        fetch('../backend/admin_process.php')
            .then(res => res.text())
            .then(html => { adminContainer.innerHTML = html; });
    }

    const profileContainer = document.getElementById('profileContainer');
    if (profileContainer) {
        fetch('../backend/profile_process.php')
            .then(res => res.text())
            .then(html => {
                profileContainer.innerHTML = html;
            })
            .catch(() => {
                profileContainer.innerHTML = '<div class="alert alert-error">Failed to load profile.</div>';
            });
    }
});