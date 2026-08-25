<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref, watch } from 'vue';
import { searchState } from '../composables/search';
import { cartCount, cartTotal, cartLines, setQty } from '../composables/cart';

const page = usePage();
const auth = computed(() => page.props.auth);

const can = (permission: string) =>
    !!auth.value?.user && auth.value.user.permissions.includes(permission);

const currentRoute = computed(() => {
    void page.url;
    return route().current() ?? '';
});

const cartOpen = ref(false);

const showCart = computed(() => {
    const current = currentRoute.value;
    return current === 'menu.catalog' && cartCount.value > 0;
});

const goCheckout = () => {
    cartOpen.value = false;
    router.get(route('checkout'));
};

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

const pageTitle = computed(() => {
    const current = currentRoute.value;
    if (current === 'dashboard') return auth.value?.user?.name ?? 'SLAMET';
    if (current === 'menu.catalog') return 'Menu Hari Ini';
    if (current === 'checkout') return 'Checkout';
    if (current === 'orders.index') return 'Pesanan Saya';
    if (current === 'orders.show') return 'Detail Pesanan';
    if (current === 'kasir.index') return 'Kasir Kantin';
    if (current === 'kasir.saldo') return 'Isi Saldo';
    if (current === 'saldo.riwayat') return 'Riwayat Saldo';
    if (current === 'saldo.transfer') return 'Transfer Saldo';
    if (current === 'reports.index') return 'Laporan';
    if (current === 'items.index') return 'Kelola Menu';
    if (current === 'items.create') return 'Tambah Menu';
    if (current === 'items.edit') return 'Edit Menu';
    if (current === 'stock.today') return 'Stok Hari Ini';
    if (current === 'masters.index') return 'Kategori';
    if (current === 'masters.create') return 'Tambah Kategori';
    if (current === 'masters.edit') return 'Edit Kategori';
    if (current === 'users.index') return 'Kelola Pengguna';
    return 'SLAMET';
});

const isSearchable = computed(() => {
    const current = currentRoute.value;
    return ['items.index', 'orders.index', 'kasir.index', 'reports.index', 'masters.index', 'users.index'].includes(current);
});

const showAdd = computed(() => {
    const current = currentRoute.value;
    return current === 'items.index' || current === 'masters.index';
});

const searching = ref(false);

const startSearch = () => { searching.value = true; };

const exitSearch = () => {
    searching.value = false;
    searchState.value = '';
};

const goCreate = () => {
    const current = currentRoute.value;
    if (current === 'items.index') {
        router.get(route('items.create'));
    } else if (current === 'masters.index') {
        router.get(route('masters.create', { entity: route().params.entity }));
    }
};

const navItems = computed(() => {
    const items: Array<{ label: string; icon: string; name: string }> = [];
    items.push({ label: 'Beranda', icon: 'home-outline', name: 'dashboard' });
    items.push({ label: 'Menu', icon: 'fire', name: 'menu.catalog' });
    if (can('order.read')) {
        items.push({ label: 'Pesanan', icon: 'notebook', name: 'orders.index' });
    }
    if (can('payment.manage')) {
        items.push({ label: 'Kasir', icon: 'qrcode-scan', name: 'kasir.index' });
    }
    if (can('report.read')) {
        items.push({ label: 'Laporan', icon: 'file-document-outline', name: 'reports.index' });
    }
    return items;
});

const showNav = computed(() => {
    const current = currentRoute.value;
    return ['dashboard', 'menu.catalog', 'orders.index', 'kasir.index', 'reports.index'].includes(current);
});

const activeIndex = ref(0);

const syncActive = () => {
    const current = currentRoute.value;
    const idx = navItems.value.findIndex((i) => {
        if (i.name === 'kasir.index') return current.startsWith('kasir.');
        return current === i.name;
    });
    activeIndex.value = idx >= 0 ? idx : 0;
};

watch(navItems, syncActive, { immediate: true });

const onTabChange = (active: string | number) => {
    const item = navItems.value[Number(active)];
    if (!item || currentRoute.value === item.name) return;
    router.get(route(item.name));
};

const showBack = computed(() => {
    const current = currentRoute.value;
    return current !== 'dashboard' && !showNav.value;
});

const goBack = () => {
    const current = currentRoute.value;
    if (current === 'checkout') {
        router.get(route('menu.catalog'));
    } else if (current.startsWith('kasir.')) {
        router.get(route('kasir.index'));
    } else if (current.startsWith('items.')) {
        router.get(route('items.index'));
    } else if (current === 'orders.show') {
        router.get(route('orders.index'));
    } else if (current.startsWith('masters.')) {
        if (current === 'masters.index') {
            router.get(route('dashboard'));
        } else {
            router.get(route('masters.index', { entity: route().params.entity }));
        }
    } else {
        router.get(route('dashboard'));
    }
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="android-layout">
        <header class="top-app-bar" :class="{ 'search-mode': searching }">
            <template v-if="!searching">
                <div class="header-left">
                    <var-button v-if="showBack" round text @click="goBack">
                        <var-icon name="arrow-left" :size="22" />
                    </var-button>
                    <span class="brand">{{ pageTitle }}</span>
                </div>
                <div class="header-right">
                    <div v-if="showCart" class="cart-pill" @click="cartOpen = true">
                        <var-icon name="cart" :size="18" />
                        <span class="cart-pill-count">{{ cartCount }}</span>
                        <span class="cart-pill-total">{{ coin(cartTotal) }}</span>
                    </div>
                    <var-button v-if="isSearchable" round text @click="startSearch">
                        <var-icon name="magnify" :size="22" />
                    </var-button>
                    <var-button round text @click="logout">
                        <var-icon name="power" :size="22" />
                    </var-button>
                </div>
            </template>
            <template v-else>
                <div class="header-left">
                    <var-button round text @click="exitSearch">
                        <var-icon name="arrow-left" :size="22" />
                    </var-button>
                </div>
                <var-input
                    v-model="searchState"
                    placeholder="Cari..."
                    clearable
                    autofocus
                    class="search-input"
                />
                <div class="header-right">
                    <var-button round text @click="exitSearch">
                        <var-icon name="close-circle-outline" :size="22" color="#475569" />
                    </var-button>
                </div>
            </template>
        </header>

        <main class="android-content">
            <var-snackbar
                :show="!!page.props.flash?.success"
                type="success"
                position="top"
                :duration="2500"
            >
                {{ page.props.flash?.success }}
            </var-snackbar>
            <var-snackbar
                :show="!!page.props.flash?.error"
                type="error"
                position="top"
                :duration="2500"
            >
                {{ page.props.flash?.error }}
            </var-snackbar>
            <slot />
        </main>

        <var-button
            v-if="showAdd"
            type="primary"
            fab
            class="fab-add"
            @click="goCreate"
        >
            <var-icon name="plus" :size="28" />
        </var-button>

        <var-bottom-navigation
            v-if="showNav"
            v-model:active="activeIndex"
            fixed
            placeholder
            @change="onTabChange"
        >
            <var-bottom-navigation-item
                v-for="item in navItems"
                :key="item.name"
                :label="item.label"
                :icon="item.icon"
            />
        </var-bottom-navigation>

        <var-popup v-model:show="cartOpen" position="bottom" class="cart-popup">
            <div class="cart-sheet">
                <div class="cart-sheet-header">
                    <span class="cart-sheet-title">Keranjang</span>
                    <var-button round text @click="cartOpen = false">
                        <var-icon name="close-circle-outline" :size="22" color="#64748b" />
                    </var-button>
                </div>

                <div v-if="cartLines.length === 0" class="cart-empty">Keranjang kosong.</div>

                <div v-for="line in cartLines" :key="line.item_id" class="cart-line">
                    <div class="cart-line-photo">
                        <img v-if="line.photo_url" :src="line.photo_url" :alt="line.name" />
                        <var-icon v-else name="image-outline" :size="18" color="#cbd5e1" />
                    </div>
                    <div class="cart-line-info">
                        <span class="cart-line-name">{{ line.name }}</span>
                        <span class="cart-line-price">{{ coin(line.price) }} × {{ line.qty }}</span>
                    </div>
                    <div class="cart-line-actions">
                        <span class="cart-line-subtotal">{{ coin(line.price * line.qty) }}</span>
                        <var-button round text class="qty-btn" @click="setQty(line.item_id, line.qty - 1)">
                            <var-icon name="minus" :size="16" />
                        </var-button>
                        <span class="qty-val">{{ line.qty }}</span>
                        <var-button round text class="qty-btn" @click="setQty(line.item_id, line.qty + 1)">
                            <var-icon name="plus" :size="16" />
                        </var-button>
                    </div>
                </div>

                <div v-if="cartLines.length > 0" class="cart-total-row">
                    <span class="cart-total-label">Total</span>
                    <span class="cart-total-value">{{ coin(cartTotal) }}</span>
                </div>

                <var-button
                    v-if="cartLines.length > 0"
                    class="cart-checkout-btn"
                    block
                    @click="goCheckout"
                >
                    Checkout
                </var-button>
            </div>
        </var-popup>
    </div>
</template>

<style scoped>
.top-app-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 14px 16px 10px 16px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-bottom: 1px solid #f57c00;
    position: sticky;
    top: 0;
    z-index: 10;
    color: #ffffff;
}

.top-app-bar .var-icon {
    color: #ffffff;
}

.top-app-bar.search-mode {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}

.top-app-bar.search-mode .var-icon {
    color: #475569;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
}

.brand {
    font-size: 17px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-right {
    display: flex;
    align-items: center;
}

.search-input {
    flex: 1;
    --field-decorator-placeholder-color: #94a3b8;
}

.fab-add {
    position: fixed !important;
    right: 20px;
    bottom: 24px;
    z-index: 99;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    box-shadow: 0 4px 12px rgba(251, 140, 0, 0.3) !important;
}

/* Cart pill di appbar */
.cart-pill {
    display: flex;
    align-items: center;
    gap: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 100px;
    padding: 4px 10px 4px 8px;
    cursor: pointer;
    white-space: nowrap;
    margin-right: 4px;
}

.cart-pill .var-icon {
    color: #ffffff;
}

.cart-pill-count {
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
}

.cart-pill-total {
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
}

/* Popup mini-cart */
.cart-sheet {
    background: #ffffff;
    border-radius: 20px 20px 0 0;
    padding: 16px 20px 24px;
    max-height: 75vh;
    overflow-y: auto;
}

.cart-sheet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.cart-sheet-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}

.cart-empty {
    text-align: center;
    padding: 24px;
    color: #94a3b8;
    font-size: 13px;
}

.cart-line-photo {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.cart-line-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    gap: 10px;
}

.cart-line-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.cart-line-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.cart-line-price {
    font-size: 11px;
    color: #94a3b8;
}

.cart-line-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.cart-line-subtotal {
    font-size: 13px;
    font-weight: 700;
    color: #f57c00;
    margin-right: 6px;
}

.cart-line-actions .qty-btn {
    color: #fb8c00;
    background: #fdf0ea;
    width: 26px;
    height: 26px;
    min-width: 26px;
    padding: 0;
}

.cart-line-actions .qty-val {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    min-width: 16px;
    text-align: center;
}

.cart-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0 8px;
}

.cart-total-label {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}

.cart-total-value {
    font-size: 18px;
    font-weight: 800;
    color: #f57c00;
}

.cart-checkout-btn {
    margin-top: 8px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
}
</style>
