<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';
import { cartLines, cartTotal, setQty, clearCart, outletId } from '../../composables/cart';

defineOptions({ layout: AppLayout });

const page = usePage();
const form = reactive({ notes: '' });
const saving = ref(false);

const showPhotoViewer = ref(false);
const currentPhotoUrl = ref('');
const currentPhotoName = ref('');

const openPhotoViewer = (url: string, name: string) => {
    currentPhotoUrl.value = url;
    currentPhotoName.value = name;
    showPhotoViewer.value = true;
};

const closePhotoViewer = () => { showPhotoViewer.value = false; };

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
const outletName = computed(() => {
    const entry = (page.props.auth?.user?.balances ?? []).find((b) => b.outlet_id === outletId.value);
    return entry?.name ?? `Kantin ${outletId.value}`;
});
const balance = computed(() => {
    const entry = (page.props.auth?.user?.balances ?? []).find((b) => b.outlet_id === outletId.value);
    return entry?.balance ?? 0;
});
const insufficient = computed(() => balance.value < cartTotal.value);
const canSubmit = computed(() => cartLines.value.length > 0 && !insufficient.value);

const errors = computed(() => (page.props as any).errors ?? {});

const submit = () => {
    if (cartLines.value.length === 0) {
        Snackbar.warning('Keranjang masih kosong.');
        return;
    }
    if (insufficient.value) {
        Snackbar.warning('Saldo tidak cukup.');
        return;
    }
    saving.value = true;
    router.post(
        route('orders.store'),
        { outlet_id: outletId.value, items: cartLines.value.map((l) => ({ item_id: l.item_id, qty: l.qty })), notes: form.notes },
        {
            onSuccess: () => {
                clearCart();
                saving.value = false;
            },
            onError: () => { saving.value = false; },
        },
    );
};
</script>

<template>
    <div class="checkout">
        <div v-if="errors.items" class="error-box">{{ errors.items }}</div>

        <div v-if="cartLines.length === 0" class="empty">
            Keranjang kosong.
            <a :href="route('menu.catalog')" class="back-link">Lihat Menu</a>
        </div>

        <template v-else>
            <div class="white-card outlet-checkout">
                <span class="outlet-checkout-label">Pesanan di</span>
                <span class="outlet-checkout-name">{{ outletName }}</span>
            </div>

            <div v-for="line in cartLines" :key="line.item_id" class="line-card">
                <div class="line-photo" style="cursor: zoom-in" @click="line.photo_url && openPhotoViewer(line.photo_url, line.name)">
                    <img v-if="line.photo_url" :src="line.photo_url" :alt="line.name" />
                    <var-icon v-else name="image-outline" :size="20" color="#cbd5e1" />
                </div>
                <div class="line-info">
                    <span class="line-name">{{ line.name }}</span>
                    <span class="line-price">{{ coin(line.price) }} × {{ line.qty }}</span>
                </div>
                <div class="line-right">
                    <span class="line-subtotal">{{ coin(line.price * line.qty) }}</span>
                    <var-button round text class="qty-btn" @click="setQty(line.item_id, line.qty - 1)">
                        <var-icon name="delete" :size="16" />
                    </var-button>
                </div>
            </div>

            <div class="white-card">
                <label class="form-label">Catatan (opsional)</label>
                <var-input
                    v-model="form.notes"
                    placeholder="Contoh: tidak pedas, porsi tambah..."
                />
            </div>

            <div class="total-card">
                <span class="total-label">Saldo Kamu</span>
                <span class="total-value">{{ coin(balance) }}</span>
            </div>

            <div class="total-card">
                <span class="total-label">Total</span>
                <span class="total-value">{{ coin(cartTotal) }}</span>
            </div>

            <div v-if="insufficient" class="error-box">
                Saldo tidak cukup. Sisa setelah dipotong: {{ coin(balance) }}.
            </div>

            <var-button
                class="submit-btn"
                block
                :loading="saving"
                :disabled="insufficient"
                @click="submit"
            >
                Buat Pesanan
            </var-button>
        </template>

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
.checkout {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
}

.back-link {
    font-size: 14px;
    color: #fb8c00;
    font-weight: 700;
    text-decoration: none;
}

.error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 12px;
    padding: 12px;
    font-size: 13px;
}

.outlet-checkout {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: #fdf0ea;
    border: 1px solid #fbd6c4;
}

.outlet-checkout-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}

.outlet-checkout-name {
    font-size: 15px;
    font-weight: 800;
    color: #f57c00;
}

.line-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    border-radius: 12px;
    padding: 10px 14px;
    border: 1px solid #f1f5f9;
    gap: 10px;
}

.line-photo {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.line-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.line-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.line-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.line-price {
    font-size: 12px;
    color: #94a3b8;
}

.line-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.line-subtotal {
    font-size: 14px;
    font-weight: 700;
    color: #f57c00;
}

.qty-btn {
    color: #ef4444;
}

.total-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 600;
}

.total-value {
    font-size: 20px;
    font-weight: 800;
    color: #f57c00;
}

.submit-btn {
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
