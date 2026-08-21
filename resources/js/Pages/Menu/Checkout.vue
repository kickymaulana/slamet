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

const rupiah = (n: number) => 'Rp ' + n.toLocaleString('id-ID');
const canSubmit = computed(() => cartLines.value.length > 0);

const errors = computed(() => (page.props as any).errors ?? {});

const submit = () => {
    if (!canSubmit.value) {
        Snackbar.warning('Keranjang masih kosong.');
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
            <var-button type="primary" round class="back-btn" @click="router.get(route('menu.catalog'))">
                Lihat Menu
            </var-button>
        </div>

        <template v-else>
            <div v-for="line in cartLines" :key="line.item_id" class="line-card">
                <div class="line-info">
                    <span class="line-name">{{ line.name }}</span>
                    <span class="line-price">{{ rupiah(line.price) }} × {{ line.qty }}</span>
                </div>
                <div class="line-right">
                    <span class="line-subtotal">{{ rupiah(line.price * line.qty) }}</span>
                    <var-button round text class="qty-btn" @click="setQty(line.item_id, line.qty - 1)">
                        <var-icon name="delete" :size="16" />
                    </var-button>
                </div>
            </div>

            <div class="white-card">
                <var-input
                    v-model="form.notes"
                    label="Catatan (opsional)"
                    placeholder="Contoh: tidak pedas, porsi tambah..."
                />
            </div>

            <div class="total-card">
                <span class="total-label">Total</span>
                <span class="total-value">{{ rupiah(cartTotal) }}</span>
            </div>

            <var-button
                class="submit-btn"
                block
                :loading="saving"
                @click="submit"
            >
                Buat Pesanan
            </var-button>
        </template>
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

.back-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
}

.error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 12px;
    padding: 12px;
    font-size: 13px;
}

.line-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #f1f5f9;
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
</style>
