<script setup lang="ts">
import { onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    order: {
        id: number;
        nota_code: string;
        user: { name: string };
        outlet?: { name: string } | null;
        paidBy?: { name: string } | null;
        total_amount: number;
        status: string;
        notes: string | null;
        paid_at: string | null;
        created_at: string;
        items: Array<{
            id: number;
            item_name: string;
            price: number;
            qty: number;
            subtotal: number;
        }>;
    };
    qr_text: string;
}>();

const qrDataUrl = ref('');
onMounted(async () => {
    try {
        qrDataUrl.value = await QRCode.toDataURL(props.qr_text, { width: 220, margin: 1 });
    } catch {
        qrDataUrl.value = '';
    }
});

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = { pending: 'warning', paid: 'success', cancelled: 'danger' };
const statusLabel: Record<string, string> = { pending: 'BELUM BAYAR', paid: 'LUNAS', cancelled: 'BATAL' };
</script>

<template>
    <div class="detail">
        <div class="white-card qr-card">
            <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Nota" class="qr-img" />
            <div v-else class="qr-fallback">{{ order.nota_code }}</div>
            <span class="qr-code">{{ order.nota_code }}</span>
            <var-chip :type="statusChip[order.status] ?? 'default'" round>
                {{ statusLabel[order.status] ?? order.status }}
            </var-chip>
            <p class="qr-hint">Tunjukkan kode ini ke kasir saat pembayaran.</p>
        </div>

        <div class="white-card">
            <div class="meta-row">
                <span class="meta-label">Pemesan</span>
                <span class="meta-value">{{ order.user?.name }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Kantin</span>
                <span class="meta-value">{{ order.outlet?.name ?? '—' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Dibuat</span>
                <span class="meta-value">{{ order.created_at }}</span>
            </div>
            <div v-if="order.paid_at" class="meta-row">
                <span class="meta-label">Dibayar</span>
                <span class="meta-value">{{ order.paid_at }} ({{ order.paidBy?.name }})</span>
            </div>
            <div v-if="order.notes" class="meta-row">
                <span class="meta-label">Catatan</span>
                <span class="meta-value">{{ order.notes }}</span>
            </div>
        </div>

        <div class="white-card">
            <div v-for="oi in order.items" :key="oi.id" class="item-row">
                <div class="item-left">
                    <span class="item-name">{{ oi.item_name }}</span>
                    <span class="item-qty">{{ coin(oi.price) }} × {{ oi.qty }}</span>
                </div>
                <span class="item-subtotal">{{ coin(oi.subtotal) }}</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total</span>
                <span class="total-value">{{ coin(order.total_amount) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.qr-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
}

.qr-img {
    width: 180px;
    height: 180px;
    border-radius: 8px;
}

.qr-fallback {
    font-family: monospace;
    font-size: 18px;
    font-weight: 800;
    padding: 60px 0;
    color: #64748b;
}

.qr-code {
    font-family: monospace;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}

.qr-hint {
    margin: 4px 0 0;
    font-size: 12px;
    color: #94a3b8;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
}

.meta-label {
    color: #94a3b8;
}

.meta-value {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.item-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.item-qty {
    font-size: 12px;
    color: #94a3b8;
}

.item-subtotal {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
}

.total-label {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}

.total-value {
    font-size: 18px;
    font-weight: 800;
    color: #f57c00;
}
</style>
