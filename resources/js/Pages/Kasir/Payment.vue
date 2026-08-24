<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Dialog, Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

defineOptions({ layout: AppLayout });

const page = usePage();
const boundOutlet = computed(() => page.props.auth?.user?.outlet_id ?? null);

const props = defineProps<{
    orders: {
        data: Array<any>;
        current_page: number;
        last_page: number;
    };
    query: string;
    outlets: Array<{ id: number; name: string }>;
    outlet: number;
}>();

searchState.value = props.query;

const searchQuery = ref(props.query);
watch(() => props.query, (v) => {
    searchQuery.value = v;
    searchState.value = v;
});

const switchOutlet = (id: number) => {
    if (id === props.outlet) return;
    router.get(route('kasir.index', { outlet: id }), {}, { preserveState: false });
};

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = { pending: 'warning', paid: 'success', cancelled: 'danger' };
const statusLabel: Record<string, string> = { pending: 'BELUM BAYAR', paid: 'LUNAS', cancelled: 'BATAL' };

const doSearch = () => {
    router.get(route('kasir.index', { q: searchQuery.value }), {}, {
        preserveState: false,
        replace: true,
    });
};

const confirmPay = (order: any) => {
    Dialog({
        title: 'Konfirmasi Pembayaran',
        message: `Lunasi ${order.nota_code} (${coin(order.total_amount)})?`,
        confirmButtonText: 'Ya, Lunas',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(route('kasir.pay', { order: order.id }), {}, {
                preserveScroll: true,
                onError: () => Snackbar.error('Gagal memproses pembayaran.'),
            });
        },
    });
};
</script>

<template>
    <div class="kasir">
        <div v-if="!boundOutlet && outlets.length > 1" class="outlet-tabs">
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

        <div class="search-bar">
            <var-input
                v-model="searchQuery"
                placeholder="Cari kode nota..."
                clearable
                @keydown.enter="doSearch"
            />
            <var-button class="search-btn" @click="doSearch">
                <var-icon name="magnify" :size="20" />
            </var-button>
        </div>

        <div v-if="orders.data.length === 0" class="empty">
            Tidak ada pesanan ditemukan.
        </div>

        <div v-for="o in orders.data" :key="o.id" class="order-card" :class="o.status">
            <div class="order-header">
                <span class="order-code">{{ o.nota_code }}</span>
                <var-chip :type="statusChip[o.status] ?? 'default'" size="small" round>
                    {{ statusLabel[o.status] ?? o.status }}
                </var-chip>
            </div>
            <div class="order-user">{{ o.user?.name }}</div>
            <div class="order-items">
                <span v-for="oi in o.items" :key="oi.id" class="order-item">
                    {{ oi.item_name }} ({{ oi.qty }})
                </span>
            </div>
            <div class="order-footer">
                <span class="order-total">{{ coin(o.total_amount) }}</span>
                <var-button
                    v-if="o.status === 'pending'"
                    type="primary"
                    size="small"
                    class="pay-btn"
                    @click="confirmPay(o)"
                >
                    Konfirmasi Lunas
                </var-button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.kasir {
    display: flex;
    flex-direction: column;
    gap: 12px;
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

.search-bar {
    display: flex;
    gap: 8px;
    align-items: center;
}

.search-bar .var-input {
    flex: 1;
}

.search-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    min-width: 44px;
    height: 44px;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.order-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 14px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.order-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 14px;
}

.order-user {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 6px;
}

.order-items {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 10px;
}

.order-item {
    font-size: 12px;
    color: #94a3b8;
    background: #f8fafc;
    padding: 2px 8px;
    border-radius: 6px;
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-total {
    font-size: 18px;
    font-weight: 800;
    color: #f57c00;
}

.pay-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
}
</style>