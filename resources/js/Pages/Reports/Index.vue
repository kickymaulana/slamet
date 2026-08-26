<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const boundOutlet = computed(() => page.props.auth?.user?.outlet_id ?? null);

const props = defineProps<{
    tab: string;
    from: string;
    to: string;
    outlets: Array<{ id: number; name: string }>;
    outlet: number | null;
    penjualan: {
        summary: {
            total_orders: number;
            total_paid: number;
            total_pending: number;
            avg_per_order: number;
        };
        orders: {
            data: Array<any>;
            current_page: number;
            last_page: number;
        };
    } | null;
    menu: Array<{ item_name: string; total_qty: number; total_revenue: number }> | null;
    topup: {
        summary: { total: number; count: number };
        transactions: Array<{
            id: number;
            user: { name: string };
            kasir: { name: string } | null;
            outlet: { name: string };
            amount: number;
            note: string | null;
            created_at: string;
        }>;
    } | null;
    stocks: Array<{ id: number; name: string; price: number; stock: number; outlet: { name: string } }> | null;
}>();

const tabs = [
    { key: 'penjualan', label: 'Penjualan' },
    { key: 'menu', label: 'Per Menu' },
    { key: 'saldo', label: 'Isi Saldo' },
    { key: 'stok', label: 'Stok' },
];

const from = ref(props.from);
const to = ref(props.to);
const outlet = ref(props.outlet ?? '');

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = { pending: 'warning', paid: 'success', cancelled: 'danger' };
const statusLabel: Record<string, string> = { pending: 'BELUM BAYAR', paid: 'LUNAS', cancelled: 'BATAL' };

const switchTab = (key: string) => {
    if (key === props.tab) return;
    const params: Record<string, string> = { tab: key, from: from.value, to: to.value };
    if (outlet.value) params.outlet = String(outlet.value);
    router.get(route('reports.index', params), {}, { preserveState: false, replace: true });
};

const applyFilter = () => {
    const params: Record<string, string> = { tab: props.tab, from: from.value, to: to.value };
    if (outlet.value) params.outlet = String(outlet.value);
    router.get(route('reports.index', params), {}, { preserveState: false, replace: true });
};

const paginate = (pageNum: number) => {
    const params: Record<string, string> = { tab: 'penjualan', from: from.value, to: to.value, page: String(pageNum) };
    if (outlet.value) params.outlet = String(outlet.value);
    router.get(route('reports.index', params), {}, { preserveScroll: true, preserveState: false });
};
</script>

<template>
    <div class="report">
        <div class="filter-card">
            <div class="tabs-row">
                <div
                    v-for="t in tabs"
                    :key="t.key"
                    class="tab-btn"
                    :class="{ active: t.key === tab }"
                    @click="switchTab(t.key)"
                >
                    {{ t.label }}
                </div>
            </div>
            <div class="filter-row">
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
                <var-button class="filter-btn" @click="applyFilter">Terapkan</var-button>
            </div>
        </div>

        <!-- TAB: Penjualan -->
        <template v-if="tab === 'penjualan' && penjualan">
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="summary-value">{{ penjualan.summary.total_orders }}</span>
                    <span class="summary-label">Total Pesanan</span>
                </div>
                <div class="summary-card">
                    <span class="summary-value">{{ coin(penjualan.summary.total_paid) }}</span>
                    <span class="summary-label">Pendapatan Lunas</span>
                </div>
                <div class="summary-card">
                    <span class="summary-value">{{ penjualan.summary.total_pending }}</span>
                    <span class="summary-label">Belum Bayar</span>
                </div>
                <div class="summary-card">
                    <span class="summary-value">{{ coin(penjualan.summary.avg_per_order) }}</span>
                    <span class="summary-label">Rata-rata / Pesanan</span>
                </div>
            </div>

            <div v-if="penjualan.orders.data.length === 0" class="empty">Tidak ada pesanan.</div>

            <div v-for="o in penjualan.orders.data" :key="o.id" class="order-row">
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

            <div v-if="penjualan.orders.last_page > 1" class="pagination">
                <span v-if="penjualan.orders.current_page > 1" class="page-btn" @click="paginate(penjualan.orders.current_page - 1)">Sebelumnya</span>
                <span class="page-info">{{ penjualan.orders.current_page }} / {{ penjualan.orders.last_page }}</span>
                <span v-if="penjualan.orders.current_page < penjualan.orders.last_page" class="page-btn" @click="paginate(penjualan.orders.current_page + 1)">Selanjutnya</span>
            </div>
        </template>

        <!-- TAB: Per Menu -->
        <template v-if="tab === 'menu' && menu">
            <div v-if="menu.length === 0" class="empty">Belum ada penjualan.</div>
            <div class="menu-table">
                <div class="menu-header">
                    <span class="menu-col-rank">#</span>
                    <span class="menu-col-name">Menu</span>
                    <span class="menu-col-qty">Qty</span>
                    <span class="menu-col-rev">Pendapatan</span>
                </div>
                <div v-for="(m, idx) in menu" :key="m.item_name" class="menu-row">
                    <span class="menu-col-rank">{{ idx + 1 }}</span>
                    <span class="menu-col-name">{{ m.item_name }}</span>
                    <span class="menu-col-qty">{{ m.total_qty }}</span>
                    <span class="menu-col-rev">{{ coin(m.total_revenue) }}</span>
                </div>
            </div>
        </template>

        <!-- TAB: Isi Saldo -->
        <template v-if="tab === 'saldo' && topup">
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="summary-value">{{ coin(topup.summary.total) }}</span>
                    <span class="summary-label">Total Topup</span>
                </div>
                <div class="summary-card">
                    <span class="summary-value">{{ topup.summary.count }}</span>
                    <span class="summary-label">Transaksi</span>
                </div>
            </div>

            <div v-if="topup.transactions.length === 0" class="empty">Belum ada topup.</div>

            <div v-for="t in topup.transactions" :key="t.id" class="order-row">
                <div class="order-top">
                    <span class="order-code">{{ t.user?.name }}</span>
                    <span class="order-amount">+{{ coin(t.amount) }}</span>
                </div>
                <div class="order-meta">
                    <span>{{ t.outlet?.name }}</span>
                    <span v-if="t.kasir">Kasir: {{ t.kasir.name }}</span>
                    <span v-if="t.note">{{ t.note }}</span>
                    <span>{{ t.created_at }}</span>
                </div>
            </div>
        </template>

        <!-- TAB: Stok -->
        <template v-if="tab === 'stok'">
            <div v-if="!stocks || stocks.length === 0" class="empty">Tidak ada stok hari ini.</div>

            <div v-for="s in stocks ?? []" :key="s.id" class="stock-row">
                <div class="stock-info">
                    <span class="stock-name">{{ s.name }}</span>
                    <span class="stock-meta">{{ s.outlet?.name }} • {{ coin(s.price) }}</span>
                </div>
                <var-chip size="mini" round :type="s.stock > 5 ? 'success' : s.stock > 0 ? 'warning' : 'danger'">
                    {{ s.stock > 5 ? 'Stok ' + s.stock : s.stock > 0 ? 'Menipis (' + s.stock + ')' : 'Habis' }}
                </var-chip>
            </div>
        </template>
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

.tabs-row {
    display: flex;
    gap: 6px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 6px 14px;
    border-radius: 100px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
}

.tab-btn.active {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-color: transparent;
    color: #ffffff;
}

.filter-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
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
    flex-shrink: 0;
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
    align-self: flex-end;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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

.order-row, .stock-row {
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

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    font-size: 13px;
    padding: 8px 0;
}

.page-btn {
    padding: 6px 16px;
    border-radius: 8px;
    background: #fff3e0;
    color: #f57c00;
    cursor: pointer;
    font-weight: 600;
}

.page-info {
    color: #64748b;
}

/* Menu table */
.menu-table {
    background: #ffffff;
    border-radius: 16px;
    padding: 0 14px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.menu-header, .menu-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}

.menu-header {
    font-weight: 700;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
}

.menu-row:last-child {
    border-bottom: none;
}

.menu-col-rank { width: 30px; text-align: center; color: #94a3b8; }
.menu-col-name { flex: 1; font-weight: 700; color: #0f172a; }
.menu-col-qty { width: 50px; text-align: right; color: #64748b; }
.menu-col-rev { width: 100px; text-align: right; font-weight: 700; color: #f57c00; }

/* Stok tab */
.stock-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stock-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stock-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.stock-meta {
    font-size: 12px;
    color: #94a3b8;
}
</style>