<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { addToCart, setQty, cart, cartCount, cartTotal, outletId } from '../../composables/cart';

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

onMounted(() => { outletId.value = props.active_outlet; });

const rupiah = (n: number) => 'Rp ' + n.toLocaleString('id-ID');

const qtyOf = (itemId: number) => cart.value[itemId]?.qty ?? 0;

const goCheckout = () => router.get(route('checkout'));

const switchOutlet = (id: number) => {
    if (id === props.active_outlet) return;
    router.get(route('menu.catalog', { outlet: id }), {}, { preserveState: false });
};

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
        <div v-if="outlets.length > 1" class="outlet-tabs">
            <div
                v-for="o in outlets"
                :key="o.id"
                class="outlet-tab"
                :class="{ active: o.id === active_outlet }"
                @click="switchOutlet(o.id)"
            >
                {{ o.name }}
            </div>
        </div>

        <div v-if="categories.length === 0" class="empty">Belum ada menu tersedia hari ini.</div>

        <section v-for="cat in categories" :key="cat.id" class="category">
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
                        {{ item.price === 0 ? 'GRATIS' : rupiah(item.price) }}
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

        <div v-if="cartCount > 0" class="cart-bar">
            <div class="cart-summary">
                <span class="cart-count">{{ cartCount }} item</span>
                <span class="cart-total">{{ rupiah(cartTotal) }}</span>
            </div>
            <var-button class="cart-checkout" @click="goCheckout">Checkout</var-button>
        </div>

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

.outlet-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.outlet-tab {
    padding: 8px 18px;
    border-radius: 100px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
}

.outlet-tab.active {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-color: transparent;
    color: #ffffff;
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

.cart-bar {
    position: sticky;
    bottom: 0;
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.06);
    margin-top: 4px;
}

.cart-summary {
    display: flex;
    flex-direction: column;
}

.cart-count {
    font-size: 12px;
    color: #64748b;
}

.cart-total {
    font-size: 16px;
    font-weight: 800;
    color: #f57c00;
}

.cart-checkout {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
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
