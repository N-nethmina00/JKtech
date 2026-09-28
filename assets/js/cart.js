// assets/js/cart.js - Cart Quantity Controls & Real-time Totals

document.addEventListener('DOMContentLoaded', () => {
    // Stepper buttons (- and +)
    const qtyInputs = document.querySelectorAll('.cart-qty-input');

    qtyInputs.forEach(input => {
        const id = input.getAttribute('data-id');
        const minusBtn = document.querySelector(`.qty-minus[data-id="${id}"]`);
        const plusBtn = document.querySelector(`.qty-plus[data-id="${id}"]`);

        if (minusBtn) {
            minusBtn.addEventListener('click', () => {
                let current = parseInt(input.value) || 1;
                if (current > 1) {
                    input.value = current - 1;
                    triggerCartUpdate(id, input.value);
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', () => {
                let current = parseInt(input.value) || 1;
                input.value = current + 1;
                triggerCartUpdate(id, input.value);
            });
        }

        input.addEventListener('change', () => {
            let current = parseInt(input.value) || 1;
            if (current < 1) current = 1;
            input.value = current;
            triggerCartUpdate(id, current);
        });
    });

    function triggerCartUpdate(productId, qty) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cart.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'product_id';
        idInput.value = productId;

        const qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'quantity';
        qtyInput.value = qty;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(qtyInput);
        document.body.appendChild(form);
        form.submit();
    }
});
