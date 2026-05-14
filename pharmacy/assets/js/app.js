document.addEventListener('DOMContentLoaded', () => {
    const selectors = {
        medicineSelect: '#medicineSelect',
        medicineQty: '#medicineQty',
        addToCartBtn: '#addToCartBtn',
        cartTableBody: '#cartTable tbody',
        cartCount: '#cartCount',
        cartTotal: '#cartTotal',
        cartData: '#cartData',
        toastContainer: '#toastContainer',
        pageAlerts: '.alert',
    };

    const elements = Object.fromEntries(
        Object.entries(selectors).map(([key, selector]) => [key, document.querySelector(selector)])
    );

    const cart = [];
    const currencyFormatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    });

    const formatMoney = (value) => currencyFormatter.format(value);
    const normaliseToastType = (type) => ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info';

    const createToast = (message, type = 'info') => {
        const container = elements.toastContainer;
        if (!container) return;

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${normaliseToastType(type)} border-0`;
        toastEl.role = 'alert';
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    const showToast = (message, type = 'info') => createToast(message, type);

    const syncCartSummary = () => {
        const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        if (elements.cartCount) elements.cartCount.textContent = String(cart.length);
        if (elements.cartTotal) elements.cartTotal.textContent = formatMoney(total);
    };

    const renderCart = () => {
        const container = elements.cartTableBody;
        if (!container) return;

        container.innerHTML = '';

        if (!cart.length) {
            container.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Cart is empty.</td>
                </tr>
            `;
            syncCartSummary();
            return;
        }

        cart.forEach((item, index) => {
            const amount = item.price * item.quantity;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="fw-500">${item.name}</td>
                <td>${item.quantity}</td>
                <td>${formatMoney(item.price)}</td>
                <td class="fw-600">${formatMoney(amount)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-index="${index}">
                        Remove
                    </button>
                </td>
            `;
            container.appendChild(row);
        });

        syncCartSummary();
    };

    const getSelectedMedicine = () => {
        const select = elements.medicineSelect;
        if (!select) return null;

        const option = select.selectedOptions[0];
        if (!option || !option.value) return null;

        return {
            id: Number(option.value),
            name: option.dataset.name || '',
            price: Number(option.dataset.price) || 0,
            stock: Number(option.dataset.stock) || 0,
        };
    };

    const addToCart = () => {
        const medicine = getSelectedMedicine();
        if (!medicine) {
            return showToast('Please choose a medicine.', 'warning');
        }

        const quantity = Number(elements.medicineQty?.value || 0);
        if (!quantity || quantity < 1) {
            return showToast('Please enter a valid quantity.', 'warning');
        }
        if (quantity > medicine.stock) {
            return showToast('Quantity exceeds available stock.', 'danger');
        }

        const existingItem = cart.find((item) => item.id === medicine.id);
        if (existingItem) {
            if (existingItem.quantity + quantity > medicine.stock) {
                return showToast('Total quantity exceeds stock for this medicine.', 'danger');
            }
            existingItem.quantity += quantity;
        } else {
            cart.push({ ...medicine, quantity });
        }

        renderCart();
        if (elements.medicineSelect) elements.medicineSelect.value = '';
        if (elements.medicineQty) elements.medicineQty.value = '1';
        showToast('Item added to cart!', 'success');
    };

    const handleCartActions = (event) => {
        const button = event.target.closest('button[data-index]');
        if (!button) return;
        const index = Number(button.dataset.index);
        cart.splice(index, 1);
        renderCart();
    };

    const prepareCartForm = () => {
        if (!cart.length) {
            showToast('Please add at least one medicine to the cart before checkout.', 'warning');
            return false;
        }
        if (elements.cartData) elements.cartData.value = JSON.stringify(cart);
        return true;
    };

    const syncPageAlerts = () => {
        document.querySelectorAll(selectors.pageAlerts).forEach((alertEl) => {
            const type = alertEl.classList.contains('alert-success')
                ? 'success'
                : alertEl.classList.contains('alert-danger')
                ? 'danger'
                : alertEl.classList.contains('alert-warning')
                ? 'warning'
                : 'info';

            const message = alertEl.textContent.trim();
            if (message) showToast(message, type);
            alertEl.remove();
        });
    };

    const enhanceForms = () => {
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', () => {
                const submitBtn = form.querySelector('[type="submit"]');
                if (!submitBtn) return;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            });
        });
    };

    const init = () => {
        elements.addToCartBtn?.addEventListener('click', addToCart);
        elements.cartTableBody?.addEventListener('click', handleCartActions);
        window.prepareCartForm = prepareCartForm;

        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', (event) => {
                event.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                target?.scrollIntoView({ behavior: 'smooth' });
            });
        });

        renderCart();
        syncPageAlerts();
        enhanceForms();
    };

    init();
});
