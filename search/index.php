<?php require_once __DIR__ . '/../includes/header.php'; ?>

<h2 style="margin-bottom: 14px;">Search & Filter Boarding Houses</h2>

<form id="searchFilterForm" class="filter-bar" data-endpoint="<?= $base_url ?>/search/fetch_listings.php">
    <div class="form-group" style="margin:0;">
        <label>Keyword / Road</label>
        <input type="text" name="q" placeholder="e.g., Katubedda, Wi-Fi">
    </div>
    <div class="form-group" style="margin:0;">
        <label>Gender</label>
        <select name="gender">
            <option value="">All Types</option>
            <option value="male">Male Only</option>
            <option value="female">Female Only</option>
            <option value="mixed">Mixed</option>
        </select>
    </div>
    <div class="form-group" style="margin:0;">
        <label>Max Monthly Rent (Rs.)</label>
        <input type="number" name="max_rent" step="500" placeholder="e.g., 20000">
    </div>
    <div class="form-group" style="margin:0;">
        <label>Max Distance (km)</label>
        <input type="number" name="max_dist" step="0.1" placeholder="e.g., 2.0">
    </div>
    <div class="form-group" style="margin:0;">
        <label>Min Rating</label>
        <select name="min_rating">
            <option value="0">Any Rating</option>
            <option value="4">4.0 ★ and above</option>
            <option value="3">3.0 ★ and above</option>
        </select>
    </div>
</form>

<div id="searchResults" class="grid"></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>