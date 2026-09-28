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

    // 4. AJAX Quick Add-to-Cart with Micro-Animation
    const quickAddButtons = document.querySelectorAll('.btn-quick-add');
    quickAddButtons.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span style="font-size:11px;">Adding...</span>';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('product_id', productId);
                formData.append('quantity', 1);

                const res = await fetch('cart.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast('✓ Added to cart successfully!', 'success');

                    // Trigger badge bump bounce animation
                    const badge = document.querySelector('.cart-badge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.classList.remove('bump');
                        void badge.offsetWidth; // trigger reflow
                        badge.classList.add('bump');
                    }
                } else {
                    showToast(data.message || 'Could not add part', 'error');
                }
            } catch (err) {
                // Fallback direct request
                window.location.href = `cart.php?action=add&id=${productId}`;
            } finally {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });
    });
});
