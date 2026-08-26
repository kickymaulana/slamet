<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: number;
            note: string | null;
            order_id: number | null;
            created_at: string;
            outlet: { name: string } | null;
        }>;
        current_page: number;
        last_page: number;
    };
}>();

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
</script>

<template>
    <div class="riwayat">
        <div v-if="transactions.data.length === 0" class="empty">Belum ada transaksi saldo.</div>

        <div v-for="t in transactions.data" :key="t.id" class="trans-card">
            <div class="trans-header">
                <span class="trans-type">{{ t.type === 'topup' ? 'Isi Saldo' : 'Transfer / Pembelian' }}</span>
                <var-chip :type="t.type === 'topup' ? 'success' : 'danger'" size="mini" round>
                    {{ t.type === 'topup' ? '+' : '-' }} {{ coin(t.amount) }}
                </var-chip>
            </div>
            <div class="trans-meta">
                <span>{{ t.outlet?.name }}</span>
                <span v-if="t.note">{{ t.note }}</span>
                <a v-if="t.type === 'deduction' && t.order_id" :href="route('orders.show', { order: t.order_id })" class="detail-link">Lihat Detail</a>
                <span>{{ t.created_at }}</span>
            </div>
        </div>

        <div class="pagination" v-if="transactions.last_page > 1">
            <span
                v-if="transactions.current_page > 1"
                class="page-btn"
                @click="router.get(route('saldo.riwayat', { page: transactions.current_page - 1 }), {}, { preserveScroll: true })"
            >Sebelumnya</span>
            <span class="page-info">{{ transactions.current_page }} / {{ transactions.last_page }}</span>
            <span
                v-if="transactions.current_page < transactions.last_page"
                class="page-btn"
                @click="router.get(route('saldo.riwayat', { page: transactions.current_page + 1 }), {}, { preserveScroll: true })"
            >Selanjutnya</span>
        </div>
    </div>
</template>

<style scoped>
.riwayat {
    display: flex;
    flex-direction: column;
    gap: 8px;
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