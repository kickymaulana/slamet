<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Dialog } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';
import { addToCart, setQty, cart, cartCount, clearCart, outletId } from '../../composables/cart';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    categories: Array<{
        id: number;
        name: string;
        items: Array<{
            id: number;
            name: string;
            description: string | null;
            price: number;
            stock: number;
            photo_url: string | null;
        }>;
    }>;
    outlets: Array<{ id: number; name: string }>;
    active_outlet: number;
}>();

const outletSel = ref(props.active_outlet);
watch(() => props.active_outlet, (v) => { outletSel.value = v; });

const searchQuery = ref('');

onMounted(() => { outletId.value = props.active_outlet; });

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

const qtyOf = (itemId: number) => cart.value[itemId]?.qty ?? 0;

const outletName = (id: number) => props.outlets.find((o) => o.id === id)?.name ?? 'Kantin';

const doSwitch = (id: number) => {
    clearCart();
    outletId.value = id;
    searchQuery.value = '';
    router.get(route('menu.catalog', { outlet: id }), {}, { preserveState: false });
};

const switchOutlet = (id: number) => {
    if (id === props.active_outlet) return;
    if (cartCount.value > 0) {
        Dialog({
            title: 'Ganti Kantin',
            message: `Keranjang berisi menu ${outletName(props.active_outlet)}. Ganti ke ${outletName(id)} akan mengosongkan keranjang.`,
            confirmButtonText: 'Ya, Kosongkan',
            cancelButtonText: 'Batal',
            onConfirm: () => doSwitch(id),
        });
    } else {
        doSwitch(id);
    }
};

const filteredCategories = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.categories;
    return props.categories
        .map((c) => ({
            ...c,
            items: c.items.filter((i) => i.name.toLowerCase().includes(q)),
        }))
        .filter((c) => c.items.length > 0);
});

const showPhotoViewer = ref(false);
const currentPhotoUrl = ref('');
const currentPhotoName = ref('');

const openPhotoViewer = (url: string, name: string) => {
    currentPhotoUrl.value = url;
    currentPhotoName.value = name;
    showPhotoViewer.value = true;
};

const closePhotoViewer = () => { showPhotoViewer.value = false; };
</script>

<template>
    <div class="catalog">
        <div class="catalog-controls">
            <var-select
                v-model="outletSel"
                class="outlet-select"
                :options="outlets.map((o) => ({ label: o.name, value: o.id }))"
                @change="switchOutlet(outletSel)"
            />
            <var-input
                v-model="searchQuery"
                placeholder="Cari menu..."
                clearable
                class="search-box"
            >
                <template #prepend-icon>
                    <var-icon name="magnify" :size="18" color="#94a3b8" />
                </template>
            </var-input>
        </div>

        <div v-if="filteredCategories.length === 0" class="empty">
            {{ searchQuery ? 'Menu tidak ditemukan.' : 'Belum ada menu tersedia hari ini.' }}
        </div>

        <section v-for="cat in filteredCategories" :key="cat.id" class="category">
            <h3 class="category-title">{{ cat.name }}</h3>
            <div v-if="cat.items.length === 0" class="empty-small">Kosong hari ini.</div>
            <div v-for="item in cat.items" :key="item.id" class="item-card">
                <div class="item-photo">
                    <img
                        v-if="item.photo_url"
                        :src="item.photo_url"
                        :alt="item.name"
                        style="cursor: zoom-in"
                        @click="openPhotoViewer(item.photo_url, item.name)"
                    />
                    <var-icon v-else name="image-outline" :size="28" color="#cbd5e1" />
                </div>
                <div class="item-info">
                    <span class="item-name">{{ item.name }}</span>
                    <span v-if="item.description" class="item-desc">{{ item.description }}</span>
                    <span class="item-price" :class="{ free: item.price === 0 }">
                        {{ item.price === 0 ? 'GRATIS' : coin(item.price) }}
                    </span>
                </div>
                <div class="item-qty">
                    <template v-if="qtyOf(item.id) > 0">
                        <var-button round text class="qty-btn" @click="setQty(item.id, qtyOf(item.id) - 1)">
                            <var-icon name="minus" :size="18" />
                        </var-button>
                        <span class="qty-val">{{ qtyOf(item.id) }}</span>
                        <var-button round text class="qty-btn" @click="setQty(item.id, qtyOf(item.id) + 1)">
                            <var-icon name="plus" :size="18" />
                        </var-button>
                    </template>
                    <var-button v-else class="add-btn" size="small" @click="addToCart({ item_id: item.id, name: item.name, price: item.price, photo_url: item.photo_url })">
                        <var-icon name="plus" :size="16" />
                    </var-button>
                </div>
            </div>
        </section>

        <!-- Photo Viewer Overlay -->
        <Transition name="fade">
            <div v-if="showPhotoViewer" class="photo-overlay" @click="closePhotoViewer">
                <div class="photo-title">{{ currentPhotoName }}</div>
                <img :src="currentPhotoUrl" alt="preview" class="photo-image" @click.stop />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.catalog {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-bottom: 12px;
}

.catalog-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.outlet-select {
    width: 130px;
    flex-shrink: 0;
}

.search-box {
    flex: 1;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.empty-small {
    font-size: 12px;
    color: #cbd5e1;
    padding: 8px 0;
}

.category-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px;
}

.item-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border-radius: 16px;
    padding: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    margin-bottom: 8px;
}

.item-photo {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.item-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.item-desc {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-price {
    font-size: 13px;
    font-weight: 700;
    color: #f57c00;
}

.item-price.free {
    color: #22c55e;
}

.item-qty {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}

.qty-btn {
    --color: #fb8c00;
    color: #fb8c00;
    background: #fdf0ea;
    width: 28px;
    height: 28px;
    min-width: 28px;
    padding: 0;
}

.qty-val {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    min-width: 20px;
    text-align: center;
}

.add-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    min-width: 34px;
    width: 34px;
    height: 34px;
    padding: 0;
}

/* Photo viewer overlay */
.photo-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.photo-title {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}

.photo-image {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
