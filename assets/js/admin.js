// assets/js/admin.js - Admin Panel Interactivity

document.addEventListener('DOMContentLoaded', () => {
    // Delete Confirmation
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const item = btn.getAttribute('data-item') || 'this item';
            if (!confirm(`Are you sure you want to delete ${item}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    // Modal handling for Add / Edit Product
    const openModalBtns = document.querySelectorAll('[data-modal-target]');
    const closeModalBtns = document.querySelectorAll('[data-modal-close]');

    openModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.style.display = 'flex';
            }
        });
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal on clicking backdrop
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.style.display = 'none';
        }
    });

    // Order status filter auto-change
    const orderStatusFilter = document.getElementById('adminOrderStatusFilter');
    if (orderStatusFilter) {
        orderStatusFilter.addEventListener('change', () => {
            const status = orderStatusFilter.value;
            window.location.href = status ? `orders.php?status=${encodeURIComponent(status)}` : 'orders.php';
        });
    }
});
