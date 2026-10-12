// assets/js/products.js - Interactive Catalog & Micro-Animations

document.addEventListener('DOMContentLoaded', () => {
    // 1. Real-time Price Slider Sync
    const priceSlider = document.getElementById('priceRangeSlider');
    const priceOutput = document.getElementById('priceRangeValue');

    if (priceSlider && priceOutput) {
        priceSlider.addEventListener('input', (e) => {
            const val = parseInt(e.target.value).toLocaleString();
            priceOutput.textContent = `Rs. ${val}`;
        });
    }

    // 2. Sort Dropdown Trigger
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortSelect.value);
            window.location.href = url.toString();
        });
    }

    // 3. Reset Button
    const resetBtn = document.getElementById('resetFiltersBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'products.php';
        });
    }
});
