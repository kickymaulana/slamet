<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    targetUser: {
        id: number;
        name: string;
        nik: string;
        balances: Array<{ outlet_id: number; name: string; balance: number }>;
    };
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: number;
            note: string | null;
            created_at: string;
            order_id: number | null;
            outlet: { name: string } | null;
            kasir: { name: string } | null;
        }>;
        current_page: number;
        last_page: number;
    };
}>();

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

const paginate = (pageNum: number) => {
    router.get(route('users.saldo', { user: props.targetUser.id, page: pageNum }), {}, {
        preserveScroll: true,
        preserveState: false,
    });
};
</script>

<template>
    <div class="saldo-detail">
        <div class="white-card">
            <div class="user-title">{{ targetUser.name }}</div>
            <div class="user-meta">NIK: {{ targetUser.nik }}</div>
            <div v-if="targetUser.balances.length === 0" class="empty-small">Belum ada saldo.</div>
            <div v-for="b in targetUser.balances" :key="b.outlet_id" class="balance-row">
                <span class="balance-outlet">{{ b.name }}</span>
                <span class="balance-value">{{ coin(b.balance) }}</span>
            </div>
        </div>

        <div class="section-title">Riwayat Transaksi Saldo</div>

        <div v-if="transactions.data.length === 0" class="empty">Belum ada transaksi.</div>

        <div v-for="t in transactions.data" :key="t.id" class="trans-card">
            <div class="trans-header">
                <span class="trans-type">{{ t.type === 'topup' ? 'Isi Saldo' : 'Pembelian' }}</span>
                <var-chip :type="t.type === 'topup' ? 'success' : 'danger'" size="mini" round>
                    {{ t.type === 'topup' ? '+' : '-' }} {{ coin(t.amount) }}
                </var-chip>
            </div>
            <div class="trans-meta">
                <span>{{ t.outlet?.name }}</span>
                <span v-if="t.kasir">Kasir: {{ t.kasir.name }}</span>
                <span v-if="t.note">{{ t.note }}</span>
                <span>{{ t.created_at }}</span>
                <a v-if="t.type === 'deduction' && t.order_id" :href="route('orders.show', { order: t.order_id })" class="detail-link">
                    Lihat Detail
                </a>
            </div>
        </div>

        <div v-if="transactions.last_page > 1" class="pagination">
            <span v-if="transactions.current_page > 1" class="page-btn" @click="paginate(transactions.current_page - 1)">Sebelumnya</span>
            <span class="page-info">{{ transactions.current_page }} / {{ transactions.last_page }}</span>
            <span v-if="transactions.current_page < transactions.last_page" class="page-btn" @click="paginate(transactions.current_page + 1)">Selanjutnya</span>
        </div>
    </div>
</template>

<style scoped>
.saldo-detail {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}

.user-meta {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 10px;
}

.empty-small {
    font-size: 12px;
    color: #94a3b8;
}

.balance-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-top: 1px solid #f1f5f9;
}

.balance-outlet {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.balance-value {
    font-size: 14px;
    font-weight: 800;
    color: #f57c00;
}

.section-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 4px 0 0;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.trans-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #f1f5f9;
}

.trans-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.trans-type {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.trans-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
    align-items: center;
}

.detail-link {
    font-size: 12px;
    color: #fb8c00;
    font-weight: 700;
    text-decoration: none;
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
</style>