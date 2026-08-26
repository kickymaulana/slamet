<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    stats: {
        today_orders: number;
        today_revenue: number;
        pending_orders: number;
        low_stock: number;
    };
    recentOrders: Array<{
        id: number;
        nota_code: string;
        user: { name: string };
        total_amount: number;
        status: string;
        created_at: string;
    }>;
    balanceTransactions: Array<{
        id: number;
        type: string;
        amount: number;
        note: string | null;
        order_id: number | null;
        outlet: { name: string };
        created_at: string;
    }>;
}>();

const page = usePage();
const can = (p: string) => !!page.props.auth?.user?.permissions.includes(p);

const go = (name: string) => router.get(route(name));

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = {
    pending: 'warning',
    paid: 'success',
    cancelled: 'danger',
};
const statusLabel: Record<string, string> = {
    pending: 'BELUM BAYAR',
    paid: 'LUNAS',
    cancelled: 'BATAL',
};

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
</script>

<template>
    <div class="dashboard">
        <div class="welcome-card">
            <span class="welcome-title">Selamat datang! 🍽️</span>
            <span class="welcome-sub">Pesan makan tanpa antre, bayar pakai saldo Coin.</span>
        </div>

        <div class="saldo-card">
            <span class="saldo-label">Saldo Saya</span>
            <div class="saldo-list">
                <div v-for="b in page.props.auth?.user?.balances ?? []" :key="b.outlet_id" class="saldo-row">
                    <span class="saldo-outlet">{{ b.name }}</span>
                    <span class="saldo-value">{{ coin(b.balance) }}</span>
                </div>
                <div v-if="(page.props.auth?.user?.balances ?? []).length === 0" class="saldo-empty">Belum ada saldo. Isi di kasir kantin.</div>
            </div>
            <a :href="route('saldo.transfer')" class="see-all-link">Transfer</a>
        </div>

        <div v-if="can('order.create')" class="feature-card order-card" @click="go('menu.catalog')">
            <div class="feature-icon">🍛</div>
            <div class="feature-text">
                <span class="feature-title">Pesan Makanan</span>
                <span class="feature-count">Lihat menu hari ini &amp; buat pesanan</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('item.read')" class="feature-card item-card" @click="go('items.index')">
            <div class="feature-icon">🍱</div>
            <div class="feature-text">
                <span class="feature-title">Kelola Menu</span>
                <span class="feature-count">Tambah/edit menu &amp; foto</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('stock.manage')" class="feature-card stock-card" @click="go('stock.today')">
            <div class="feature-icon">📦</div>
            <div class="feature-text">
                <span class="feature-title">Stok Hari Ini</span>
                <span class="feature-count">Atur stok menu per hari</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('payment.manage')" class="feature-card kasir-card" @click="go('kasir.index')">
            <div class="feature-icon">💳</div>
            <div class="feature-text">
                <span class="feature-title">Kasir</span>
                <span class="feature-count">Cari nota &amp; konfirmasi pembayaran</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('report.read')" class="feature-card report-card" @click="go('reports.index')">
            <div class="feature-icon">📊</div>
            <div class="feature-text">
                <span class="feature-title">Laporan</span>
                <span class="feature-count">Rekap pesanan &amp; pendapatan</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div v-if="can('user.manage')" class="feature-card users-card" @click="go('users.index')">
            <div class="feature-icon">👥</div>
            <div class="feature-text">
                <span class="feature-title">Kelola Pengguna</span>
                <span class="feature-count">Setujui &amp; atur role user</span>
            </div>
            <var-icon name="chevron-right" :size="24" color="#94a3b8" />
        </div>

        <div class="section-header">
            <h3 class="section-title">Pesanan Terbaru</h3>
            <a :href="route('orders.index')" class="see-all-link">Lihat Semua</a>
        </div>

        <div v-if="recentOrders.length === 0" class="empty-card">Belum ada pesanan.</div>

        <div v-else class="request-list">
            <div v-for="o in recentOrders" :key="o.id" class="request-card">
                <div class="request-header">
                    <span class="request-code">{{ o.nota_code }}</span>
                    <var-chip :type="statusChip[o.status] ?? 'default'" size="small" round>
                        {{ statusLabel[o.status] ?? o.status }}
                    </var-chip>
                </div>
                <div class="request-meta">
                    <span>{{ o.user?.name }}</span>
                    <span>{{ coin(o.total_amount) }}</span>
                    <span>{{ o.created_at }}</span>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h3 class="section-title">Riwayat Saldo</h3>
            <a :href="route('saldo.riwayat')" class="see-all-link">Lihat Semua</a>
        </div>

        <div v-if="balanceTransactions.length === 0" class="empty-card">Belum ada transaksi saldo.</div>

        <div v-else class="request-list">
            <div v-for="t in balanceTransactions" :key="t.id" class="request-card">
                <div class="request-header">
                    <span class="request-code">{{ t.type === 'topup' ? 'Isi Saldo' : 'Pembelian' }}</span>
                    <var-chip :type="t.type === 'topup' ? 'success' : 'danger'" size="small" round>
                        {{ t.type === 'topup' ? '+' : '-' }} {{ coin(t.amount) }}
                    </var-chip>
                </div>
                <div class="request-meta">
                    <span>{{ t.outlet?.name }}</span>
                    <span v-if="t.note">{{ t.note }}</span>
                    <a v-if="t.type === 'deduction' && t.order_id" :href="route('orders.show', { order: t.order_id })" class="detail-link">Lihat Detail</a>
                    <span>{{ t.created_at }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.welcome-card {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-radius: 20px;
    padding: 20px;
    color: #ffffff;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.welcome-title {
    font-size: 18px;
    font-weight: 800;
}

.welcome-sub {
    font-size: 13px;
    opacity: 0.9;
}

.saldo-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.saldo-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 600;
}

.saldo-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.saldo-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.saldo-outlet {
    font-size: 13px;
    color: #1e293b;
    font-weight: 600;
}

.saldo-value {
    font-size: 18px;
    font-weight: 800;
    color: #f57c00;
}

.saldo-empty {
    font-size: 12px;
    color: #94a3b8;
}

.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #f57c00;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
}

.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #f57c00;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
}

.feature-card {
    display: flex;
    align-items: center;
    gap: 14px;
    border-radius: 16px;
    padding: 16px;
    border: 2px solid;
    cursor: pointer;
}

.order-card { background: #fdf0ea; border-color: #fbd6c4; }
.item-card { background: #fef3c7; border-color: #fde68a; }
.stock-card { background: #f0fdf4; border-color: #bbf7d0; }
.kasir-card { background: #ecfeff; border-color: #a5f3fc; }
.report-card { background: #eef2ff; border-color: #c7d2fe; }
.users-card { background: #f5f3ff; border-color: #ddd6fe; }

.feature-icon { font-size: 32px; }
.feature-text { flex: 1; display: flex; flex-direction: column; }
.feature-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.feature-count { font-size: 12px; color: #64748b; }

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}

.section-title { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; }

.see-all-link {
    font-size: 12px;
    color: #fb8c00;
    text-decoration: none;
    font-weight: 600;
}

.detail-link {
    font-size: 12px;
    color: #fb8c00;
    font-weight: 700;
    text-decoration: none;
}

.request-list { display: flex; flex-direction: column; gap: 12px; }

.request-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.request-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 13px;
}

.request-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}

.empty-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    border: 1px dashed #cbd5e1;
    color: #94a3b8;
    font-size: 13px;
}
</style>
