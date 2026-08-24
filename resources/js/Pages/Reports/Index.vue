<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const boundOutlet = computed(() => page.props.auth?.user?.outlet_id ?? null);

const props = defineProps<{
    orders: {
        data: Array<any>;
        current_page: number;
        last_page: number;
    };
    summary: {
        total_orders: number;
        total_paid: number;
        total_pending: number;
    };
    from: string;
    to: string;
    outlets: Array<{ id: number; name: string }>;
    outlet: number | null;
}>();

const from = ref(props.from);
const to = ref(props.to);
const outlet = ref(props.outlet ?? '');

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = { pending: 'warning', paid: 'success', cancelled: 'danger' };
const statusLabel: Record<string, string> = { pending: 'BELUM BAYAR', paid: 'LUNAS', cancelled: 'BATAL' };

const applyFilter = () => {
    const params: Record<string, string> = { from: from.value, to: to.value };
    if (outlet.value) params.outlet = String(outlet.value);
    router.get(route('reports.index', params), {}, { preserveState: false, replace: true });
};
</script>

<template>
    <div class="report">
        <div class="filter-card">
            <var-space direction="column" size="small">
                <div class="date-row">
                    <label class="date-label">Dari</label>
                    <input v-model="from" type="date" class="date-input" />
                </div>
                <div class="date-row">
                    <label class="date-label">Sampai</label>
                    <input v-model="to" type="date" class="date-input" />
                </div>
                <div v-if="!boundOutlet" class="date-row">
                    <label class="date-label">Kantin</label>
                    <select v-model="outlet" class="date-input">
                        <option value="">Semua</option>
                        <option v-for="o in outlets" :key="o.id" :value="o.id">{{ o.name }}</option>
                    </select>
                </div>
                <var-button class="filter-btn" block @click="applyFilter">Terapkan</var-button>
            </var-space>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-value">{{ summary.total_orders }}</span>
                <span class="summary-label">Total Pesanan</span>
            </div>
            <div class="summary-card">
                <span class="summary-value">{{ coin(summary.total_paid) }}</span>
                <span class="summary-label">Pendapatan Lunas</span>
            </div>
            <div class="summary-card">
                <span class="summary-value">{{ summary.total_pending }}</span>
                <span class="summary-label">Belum Bayar</span>
            </div>
        </div>

        <div v-if="orders.data.length === 0" class="empty">Tidak ada pesanan pada periode ini.</div>

        <div v-for="o in orders.data" :key="o.id" class="order-row">
            <div class="order-top">
                <span class="order-code">{{ o.nota_code }}</span>
                <var-chip :type="statusChip[o.status] ?? 'default'" size="mini" round>
                    {{ statusLabel[o.status] ?? o.status }}
                </var-chip>
            </div>
            <div class="order-meta">
                <span>{{ o.user?.name }}</span>
                <span>{{ o.outlet?.name }}</span>
                <span>{{ o.created_at }}</span>
                <span class="order-amount">{{ coin(o.total_amount) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.filter-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
}

.date-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.date-label {
    font-size: 13px;
    color: #64748b;
    width: 60px;
}

.date-input {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 14px;
}

.filter-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.summary-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 14px;
    border: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: center;
}

.summary-value {
    font-size: 16px;
    font-weight: 800;
    color: #f57c00;
}

.summary-label {
    font-size: 11px;
    color: #64748b;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.order-row {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #f1f5f9;
    margin-bottom: 8px;
}

.order-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.order-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 13px;
}

.order-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
    align-items: center;
}

.order-amount {
    font-weight: 700;
    color: #f57c00;
}
</style>