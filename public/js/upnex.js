/**
 * UPNEX — JavaScript AJAX Utilities
 * Tất cả AJAX dùng Fetch API + CSRF token
 */

const UPNEX = (function () {

    // ── CSRF helper ──────────────────────────────────────────
    // Token được nhúng vào meta tag trong header.php
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // ── Fetch helper (POST) ──────────────────────────────────
    async function post(url, data = {}) {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));

        const res = await fetch(url, { method: 'POST', body: formData });
        return res.json();
    }

    // ── Fetch helper (GET) ───────────────────────────────────
    async function get(url) {
        const res = await fetch(url);
        return res.json();
    }

    // ════════════════════════════════════════════════════════
    //  GIỎ HÀNG
    // ════════════════════════════════════════════════════════

    async function addToCart(productId, quantity = 1, btn = null) {
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

        try {
            const data = await post('?case=add_to_cart', { product_id: productId, quantity });
            if (data.success) {
                updateCartBadge(data.cart_count);
                showToast('Đã thêm vào giỏ hàng!', 'success');
            } else {
                showToast(data.message || 'Không thể thêm vào giỏ.', 'danger');
                // Nếu chưa đăng nhập → redirect
                if (data.redirect) window.location.href = data.redirect;
            }
        } catch {
            showToast('Lỗi kết nối, thử lại sau.', 'danger');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-cart-plus"></i> Thêm vào giỏ'; }
        }
    }

    async function updateCart(cartId, quantity, row = null) {
        const data = await post('?case=update_cart', { cart_id: cartId, quantity });
        if (data.success) {
            refreshCartPage();
        } else {
            showToast(data.message, 'warning');
        }
    }

    async function removeFromCart(cartId) {
        if (!confirm('Xóa sản phẩm này khỏi giỏ?')) return;
        const data = await post('?case=remove_cart', { cart_id: cartId });
        if (data.success) {
            updateCartBadge(data.cart_count);
            refreshCartPage();
        }
    }

    function refreshCartPage() {
        window.location.reload();
    }

    function updateCartBadge(count) {
        document.querySelectorAll('.cart-badge').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'inline' : 'none';
        });
    }

    // ════════════════════════════════════════════════════════
    //  VOUCHER
    // ════════════════════════════════════════════════════════

    async function applyVoucher() {
        const code     = document.getElementById('voucher-code')?.value.trim();
        const subtotal = document.getElementById('subtotal-value')?.dataset.value || 0;
        const msgEl    = document.getElementById('voucher-msg');

        if (!code) { showToast('Nhập mã giảm giá.', 'warning'); return; }

        const data = await post('?case=apply_voucher', { code, subtotal });
        if (msgEl) msgEl.textContent = data.message;
        msgEl?.classList.toggle('text-success', data.success);
        msgEl?.classList.toggle('text-danger', !data.success);

        if (data.success) {
            document.getElementById('discount-amount').textContent = '-' + formatVND(data.discount);
            document.getElementById('hidden-voucher').value        = code;
            document.getElementById('hidden-discount').value       = data.discount;
            recalcTotal();
        }
    }

    // ════════════════════════════════════════════════════════
    //  SEARCH AUTOCOMPLETE
    // ════════════════════════════════════════════════════════

    let searchTimeout = null;

    function initSearchAutocomplete() {
        const input   = document.getElementById('search-input');
        const suggest = document.getElementById('search-suggest');
        if (!input || !suggest) return;

        input.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const q = input.value.trim();
            if (q.length < 2) { suggest.innerHTML = ''; suggest.style.display = 'none'; return; }

            searchTimeout = setTimeout(async () => {
                const data = await get('?case=search_ajax&q=' + encodeURIComponent(q));
                if (!data.products?.length) { suggest.style.display = 'none'; return; }

                suggest.innerHTML = data.products.map(p => `
                    <a href="?case=product_detail&id=${p.id}" class="d-flex align-items-center gap-2 p-2 text-decoration-none text-dark border-bottom">
                        <img src="${p.image ? '/upnex/uploads/products/' + p.image : '/upnex/public/images/placeholder.png'}" width="40" height="40" style="object-fit:cover;border-radius:4px">
                        <div>
                            <div class="small fw-semibold">${p.name}</div>
                            <div class="small text-danger">${p.price}đ</div>
                        </div>
                    </a>`).join('');
                suggest.style.display = 'block';
            }, 300);
        });

        // Đóng gợi ý khi click ngoài
        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !suggest.contains(e.target)) {
                suggest.style.display = 'none';
            }
        });
    }

    // ════════════════════════════════════════════════════════
    //  HỦY ĐƠN
    // ════════════════════════════════════════════════════════

    async function cancelOrder(orderId) {
        if (!confirm('Bạn có chắc muốn hủy đơn hàng này?')) return;
        const data = await post('?case=cancel_order', { order_id: orderId });
        if (data.success) {
            showToast('Đơn hàng đã được hủy.', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.message, 'danger');
        }
    }

    // ════════════════════════════════════════════════════════
    //  ADMIN: Cập nhật trạng thái đơn (AJAX)
    // ════════════════════════════════════════════════════════

    async function adminUpdateOrder(orderId, status, note = '') {
        const data = await post('?case=admin_order_update', { order_id: orderId, status, note });
        if (data.success) {
            showToast('Cập nhật thành công!', 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(data.message || 'Lỗi cập nhật.', 'danger');
        }
    }

    // ════════════════════════════════════════════════════════
    //  ADMIN: Khóa/mở user
    // ════════════════════════════════════════════════════════

    async function adminToggleUser(userId, btn) {
        const data = await post('?case=admin_user_lock', { id: userId });
        if (data.success) {
            const isLocked = btn.dataset.locked === '1';
            btn.dataset.locked = isLocked ? '0' : '1';
            btn.textContent    = isLocked ? 'Khóa' : 'Mở khóa';
            btn.className      = 'btn btn-sm ' + (isLocked ? 'btn-outline-danger' : 'btn-outline-success');
            showToast(isLocked ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.', 'info');
        }
    }

    // ════════════════════════════════════════════════════════
    //  ADMIN: Ẩn/hiện review
    // ════════════════════════════════════════════════════════

    async function adminToggleReview(reviewId, btn) {
        const data = await post('?case=admin_review_toggle', { id: reviewId });
        if (data.success) {
            const visible = btn.dataset.visible === '1';
            btn.dataset.visible = visible ? '0' : '1';
            btn.textContent     = visible ? 'Hiện' : 'Ẩn';
        }
    }

    // ════════════════════════════════════════════════════════
    //  UTILS
    // ════════════════════════════════════════════════════════

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const id    = 'toast-' + Date.now();
        const icons = { success: '✓', danger: '✕', warning: '⚠', info: 'ℹ' };
        container.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${icons[type] || ''} ${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`);
        const el    = document.getElementById(id);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    function formatVND(amount) {
        return parseInt(amount).toLocaleString('vi-VN');
    }

    function recalcTotal() {
        const subtotal  = parseInt(document.getElementById('subtotal-value')?.dataset.value || 0);
        const discount  = parseInt(document.getElementById('hidden-discount')?.value || 0);
        const shipping  = 30000;
        const total     = subtotal - discount + shipping;
        const totalEl   = document.getElementById('total-value');
        if (totalEl) totalEl.textContent = formatVND(total) + 'đ';
    }

    // ── Init khi DOM sẵn ─────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSearchAutocomplete();
    });

    // Public API
    return { addToCart, updateCart, removeFromCart, applyVoucher, cancelOrder, adminUpdateOrder, adminToggleUser, adminToggleReview, showToast };

})();
