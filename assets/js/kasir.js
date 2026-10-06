// Hybrid POS Shopping Cart System for PetCare
let cart = [];
let currentCategory = 'all';

function addToCart(item) {
    const existingIndex = cart.findIndex(c => c.id_barang === item.id_barang);
    if (existingIndex > -1) {
        if (item.tipe_item === 'retail' || item.tipe_item === 'repack') {
            if (cart[existingIndex].jumlah >= item.stok) {
                alert('Stok barang tidak mencukupi! Tersisa: ' + item.stok);
                return;
            }
        }
        cart[existingIndex].jumlah += 1;
    } else {
        if ((item.tipe_item === 'retail' || item.tipe_item === 'repack') && item.stok <= 0) {
            alert('Stok barang habis!');
            return;
        }
        cart.push({
            id_barang: item.id_barang,
            kode_barang: item.kode_barang,
            nama_barang: item.nama_barang,
            tipe_item: item.tipe_item,
            harga_satuan: parseFloat(item.harga_jual),
            jumlah: 1
        });
    }
    renderCart();
}

function updateCartQty(index, newQty) {
    newQty = parseInt(newQty);
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    cart[index].jumlah = newQty;
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

function renderCart() {
    const cartItemsEl = document.getElementById('cartItems');
    const totalBayarEl = document.getElementById('totalBayarDisplay');
    const cartInputEl = document.getElementById('cartInput');
    const serviceAlertEl = document.getElementById('serviceAlert');

    if (!cartItemsEl) return;

    if (cart.length === 0) {
        cartItemsEl.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 30px;">Keranjang kosong. Klik produk di sebelah kiri untuk menambahkan.</div>';
        totalBayarEl.innerText = 'Rp 0';
        cartInputEl.value = JSON.stringify([]);
        if (serviceAlertEl) serviceAlertEl.style.display = 'none';
        return;
    }

    let total = 0;
    let hasGroomingService = false;
    let html = '';

    cart.forEach((item, idx) => {
        const subtotal = item.harga_satuan * item.jumlah;
        total += subtotal;
        if (item.tipe_item === 'jasa') {
            hasGroomingService = true;
        }

        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.95rem; color: #1e293b;">
                        ${item.nama_barang}
                        ${item.tipe_item === 'jasa' ? '<span class="badge badge-info" style="font-size: 0.7rem;">Jasa Grooming</span>' : ''}
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        Rp ${item.harga_satuan.toLocaleString('id-ID')}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="updateCartQty(${idx}, ${item.jumlah - 1})">-</button>
                    <span style="font-weight: bold; width: 25px; text-align: center;">${item.jumlah}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="updateCartQty(${idx}, ${item.jumlah + 1})">+</button>
                </div>
                <div style="font-weight: bold; width: 90px; text-align: right; color: #0f172a;">
                    Rp ${subtotal.toLocaleString('id-ID')}
                </div>
                <button type="button" onclick="removeFromCart(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px 8px; margin-left: 5px; font-size: 1.1rem;">
                    &times;
                </button>
            </div>
        `;
    });

    cartItemsEl.innerHTML = html;
    totalBayarEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
    cartInputEl.value = JSON.stringify(cart);

    if (serviceAlertEl) {
        serviceAlertEl.style.display = hasGroomingService ? 'block' : 'none';
    }

    calculateChange();
}

function calculateChange() {
    const bayarInput = document.getElementById('nominalBayar');
    const kembalianDisplay = document.getElementById('kembalianDisplay');
    if (!bayarInput || !kembalianDisplay) return;

    let total = cart.reduce((sum, item) => sum + (item.harga_satuan * item.jumlah), 0);
    let bayar = parseFloat(bayarInput.value) || 0;
    let kembali = bayar - total;

    if (kembali >= 0) {
        kembalianDisplay.innerText = 'Rp ' + kembali.toLocaleString('id-ID');
        kembalianDisplay.style.color = '#10b981';
    } else {
        kembalianDisplay.innerText = '- Rp ' + Math.abs(kembali).toLocaleString('id-ID');
        kembalianDisplay.style.color = '#ef4444';
    }
}

function filterProducts(type) {
    currentCategory = type;
    const cards = document.querySelectorAll('.product-item-card');
    cards.forEach(card => {
        const itemType = card.getAttribute('data-type');
        if (type === 'all' || itemType === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    document.querySelectorAll('.filter-pill').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-type') === type) {
            btn.classList.add('active');
        }
    });
}

function searchProducts(keyword) {
    keyword = keyword.toLowerCase();
    const cards = document.querySelectorAll('.product-item-card');
    cards.forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const code = (card.getAttribute('data-code') || '').toLowerCase();
        const type = card.getAttribute('data-type');

        const matchesSearch = name.includes(keyword) || code.includes(keyword);
        const matchesCategory = (currentCategory === 'all' || type === currentCategory);

        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
