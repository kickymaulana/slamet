<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    items: Array<{
        id: number;
        name: string;
        price: number;
        stock: number;
        stock_date: string | null;
        category_id: number;
    }>;
    today: string;
    outlets: Array<{ id: number; name: string }>;
    outlet: number;
}>();

const switchOutlet = (id: number) => {
    if (id === props.outlet) return;
    router.get(route('stock.today', { outlet: id }), {}, { preserveState: false });
};

const stocks = reactive<Record<number, string>>({});
props.items.forEach((i) => {
    stocks[i.id] = i.stock_date === props.today ? String(i.stock) : '0';
});

const saving = ref(false);
const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

const save = () => {
    saving.value = true;
    const entries = props.items.map((i) => ({
        id: i.id,
        stock: parseInt(stocks[i.id], 10) || 0,
    }));
    router.post(route('stock.save'), { items: entries }, {
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal menyimpan stok.'); },
    });
};
</script>

<template>
    <div class="stock">
        <div v-if="outlets.length > 1" class="outlet-tabs">
            <div
                v-for="o in outlets"
                :key="o.id"
                class="outlet-tab"
                :class="{ active: o.id === outlet }"
                @click="switchOutlet(o.id)"
            >
                {{ o.name }}
            </div>
        </div>

        <p class="date-note">Tanggal: {{ today }}</p>

        <div v-if="items.length === 0" class="empty">Belum ada menu aktif.</div>

        <div v-for="i in items" :key="i.id" class="stock-row">
            <div class="stock-info">
                <span class="stock-name">{{ i.name }}</span>
                <span class="stock-price">{{ coin(i.price) }}</span>
            </div>
            <var-input
                v-model="stocks[i.id]"
                type="number"
                placeholder="0"
                class="stock-input"
            />
        </div>

        <var-button class="save-btn" block :loading="saving" @click="save">
            Simpan Stok Hari Ini
        </var-button>
    </div>
</template>

<style scoped>
.stock {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.outlet-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
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

.date-note {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.stock-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
    border-radius: 12px;
    padding: 10px 14px;
    border: 1px solid #f1f5f9;
}

.stock-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.stock-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.stock-price {
    font-size: 12px;
    color: #94a3b8;
}

.stock-input {
    width: 90px;
}

.save-btn {
    margin-top: 8px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
}
</style>