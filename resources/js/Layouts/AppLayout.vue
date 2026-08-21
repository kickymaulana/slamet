<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref, watch } from 'vue';
import { searchState } from '../composables/search';

const page = usePage();
const auth = computed(() => page.props.auth);

const can = (permission: string) =>
    !!auth.value?.user && auth.value.user.permissions.includes(permission);

const pageTitle = computed(() => {
    const current = route().current() ?? '';
    if (current === 'dashboard') return auth.value?.user?.name ?? 'SLAMET';
    if (current === 'menu.catalog') return 'Menu Hari Ini';
    if (current === 'checkout') return 'Checkout';
    if (current === 'orders.index') return 'Pesanan Saya';
    if (current === 'orders.show') return 'Detail Pesanan';
    if (current === 'kasir.index') return 'Kasir Kantin';
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
    const current = route().current() ?? '';
    return ['items.index', 'orders.index', 'kasir.index', 'reports.index', 'masters.index', 'users.index'].includes(current);
});

const showAdd = computed(() => {
    const current = route().current() ?? '';
    return current === 'items.index' || current === 'masters.index';
});

const searching = ref(false);

const startSearch = () => { searching.value = true; };

const exitSearch = () => {
    searching.value = false;
    searchState.value = '';
};

const goCreate = () => {
    const current = route().current() ?? '';
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
    const current = route().current() ?? '';
    return ['dashboard', 'menu.catalog', 'orders.index', 'kasir.index', 'reports.index'].includes(current);
});

const activeIndex = ref(0);

const syncActive = () => {
    const current = route().current();
    const idx = navItems.value.findIndex((i) => (current ?? '') === i.name);
    activeIndex.value = idx >= 0 ? idx : 0;
};

watch(navItems, syncActive, { immediate: true });

const onTabChange = (active: string | number) => {
    const item = navItems.value[Number(active)];
    if (!item || route().current() === item.name) return;
    router.get(route(item.name));
};

const showBack = computed(() => {
    const current = route().current() ?? '';
    return current !== 'dashboard' && !showNav.value;
});

const goBack = () => {
    const current = route().current() ?? '';
    if (current === 'checkout') {
        router.get(route('menu.catalog'));
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
</style>
