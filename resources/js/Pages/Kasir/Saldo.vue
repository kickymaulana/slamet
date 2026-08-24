<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Dialog, Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const boundOutlet = computed(() => page.props.auth?.user?.outlet_id ?? null);

const props = defineProps<{
    outlets: Array<{ id: number; name: string }>;
    outlet: number;
    topups: Array<{
        id: number;
        user: { name: string };
        amount: number;
        note: string | null;
        created_at: string;
    }>;
}>();

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
const shortCoin = (n: number) => (n >= 1000 ? Math.round(n / 1000) + 'rb' : String(n));

const switchOutlet = (id: number) => {
    if (id === props.outlet) return;
    router.get(route('kasir.saldo', { outlet: id }), {}, { preserveState: false });
};

const topupNik = ref('');
const topupAmount = ref('');
const topupNote = ref('');
const userResult = ref<{ id: number; name: string; nik: string; balance: number } | null>(null);
const checking = ref(false);
const savingTopup = ref(false);
const QUICK = [10000, 25000, 50000, 100000];

const checkUser = async () => {
    const nik = topupNik.value.trim();
    if (!nik) return;
    checking.value = true;
    userResult.value = null;
    try {
        const res = await fetch(route('kasir.user', { nik }), { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            Snackbar.warning('User tidak ditemukan.');
            return;
        }
        userResult.value = await res.json();
    } catch {
        Snackbar.error('Gagal cek user.');
    } finally {
        checking.value = false;
    }
};

const confirmTopup = () => {
    if (!userResult.value) {
        Snackbar.warning('Cek NIK dulu.');
        return;
    }
    const amount = parseInt(topupAmount.value, 10);
    if (!amount || amount <= 0) {
        Snackbar.warning('Masukkan nominal.');
        return;
    }
    Dialog({
        title: 'Isi Saldo',
        message: `Isi saldo ${userResult.value.name} sebesar ${coin(amount)}?`,
        confirmButtonText: 'Ya, Isi',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            savingTopup.value = true;
            router.post(route('kasir.topup'), {
                nik: topupNik.value.trim(),
                amount,
                note: topupNote.value,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    savingTopup.value = false;
                    userResult.value = null;
                    topupAmount.value = '';
                    topupNote.value = '';
                },
                onError: () => {
                    savingTopup.value = false;
                    Snackbar.error('Gagal isi saldo.');
                },
            });
        },
    });
};
</script>

<template>
    <div class="saldo">
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

        <div class="white-card topup-card">
            <div class="topup-title">Isi Saldo</div>
            <label class="form-label">NIK Karyawan</label>
            <div class="topup-nik-row">
                <var-input v-model="topupNik" placeholder="Contoh: K190798" clearable />
                <var-button class="check-btn" :loading="checking" @click="checkUser">Cek</var-button>
            </div>
            <div v-if="userResult" class="topup-user">
                <span class="topup-user-name">{{ userResult.name }}</span>
                <span class="topup-user-balance">Saldo {{ coin(userResult.balance) }}</span>
            </div>

            <label class="form-label">Pilih nominal</label>
            <div class="topup-quick">
                <button
                    v-for="q in QUICK"
                    :key="q"
                    type="button"
                    class="quick-btn"
                    :class="{ active: topupAmount === String(q) }"
                    @click="topupAmount = String(q)"
                >
                    {{ shortCoin(q) }}
                </button>
            </div>

            <label class="form-label">Nominal</label>
            <var-input v-model="topupAmount" type="number" placeholder="Atau isi manual, contoh: 15000" />
            <label class="form-label">Catatan (opsional)</label>
            <var-input v-model="topupNote" placeholder="Contoh: isi 100k" />
            <var-button
                class="topup-submit"
                block
                :loading="savingTopup"
                @click="confirmTopup"
            >
                Isi Saldo
            </var-button>
        </div>

        <div v-if="topups.length > 0" class="white-card">
            <div class="topup-title">Riwayat Isi Saldo</div>
            <div v-for="t in topups" :key="t.id" class="topup-history-row">
                <div class="topup-history-info">
                    <span class="topup-history-name">{{ t.user?.name }}</span>
                    <span class="topup-history-meta">{{ t.note ?? 'Isi saldo' }} • {{ t.created_at }}</span>
                </div>
                <span class="topup-history-amount">+{{ coin(t.amount) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.saldo {
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

.topup-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.topup-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.field-label {
    font-size: 13px;
    color: #64748b;
}

.topup-nik-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.topup-nik-row .var-input {
    flex: 1;
}

.check-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    flex-shrink: 0;
    height: 40px;
}

.topup-user {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topup-user-name {
    font-size: 14px;
    font-weight: 700;
    color: #166534;
}

.topup-user-balance {
    font-size: 13px;
    font-weight: 700;
    color: #16a34a;
}

.topup-quick {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.quick-btn {
    padding: 12px 0;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
}

.quick-btn.active {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    border-color: transparent;
    color: #ffffff;
}

.topup-submit {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
}

.topup-history-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.topup-history-row:last-child {
    border-bottom: none;
}

.topup-history-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.topup-history-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.topup-history-meta {
    font-size: 11px;
    color: #94a3b8;
}

.topup-history-amount {
    font-size: 14px;
    font-weight: 800;
    color: #22c55e;
}
</style>
