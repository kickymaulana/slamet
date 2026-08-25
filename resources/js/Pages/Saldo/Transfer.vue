<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Dialog, Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();

const props = defineProps<{
    outlets: Array<{ id: number; name: string }>;
}>();

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
const shortCoin = (n: number) => (n >= 1000 ? Math.round(n / 1000) + 'rb' : String(n));

const balances = computed(() => page.props.auth?.user?.balances ?? []);
const outletSel = ref(balances.value[0]?.outlet_id ?? '');
const receiverNik = ref('');
const amount = ref('');
const note = ref('');
const userResult = ref<{ id: number; name: string; nik: string; balance: number } | null>(null);
const checking = ref(false);
const saving = ref(false);
const QUICK = [10000, 25000, 50000, 100000];

const checkUser = async () => {
    const nik = receiverNik.value.trim();
    if (!nik) return;
    checking.value = true;
    userResult.value = null;
    try {
        const res = await fetch(route('kasir.user', { nik }), { headers: { Accept: 'application/json' } });
        if (!res.ok) { Snackbar.warning('User tidak ditemukan.'); return }
        userResult.value = await res.json();
    } catch { Snackbar.error('Gagal cek user.'); }
    finally { checking.value = false }
};

const confirmTransfer = () => {
    const amt = parseInt(amount.value, 10);
    if (!userResult.value) { Snackbar.warning('Cek NIK dulu.'); return }
    if (!amt || amt <= 0) { Snackbar.warning('Masukkan nominal.'); return }
    const outletName = props.outlets.find(o => o.id === Number(outletSel.value))?.name ?? '';
    Dialog({
        title: 'Transfer Saldo',
        message: `Transfer ${coin(amt)} ke ${userResult.value.name} (${outletName})?`,
        confirmButtonText: 'Ya, Transfer',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            saving.value = true;
            router.post(route('saldo.transfer.submit'), {
                outlet_id: outletSel.value,
                nik: receiverNik.value.trim(),
                amount: amt,
                note: note.value,
            }, {
                onSuccess: () => { saving.value = false; userResult.value = null; amount.value = ''; note.value = ''; },
                onError: () => { saving.value = false; Snackbar.error('Gagal transfer.'); },
            });
        },
    });
};
</script>

<template>
    <div class="transfer">
        <div class="white-card">
            <div class="section-title">Pilih Sumber Saldo</div>
            <var-select
                v-model="outletSel"
                placeholder="Pilih kantin"
                :options="balances.map((b) => ({ label: b.name + ' (' + coin(b.balance) + ')', value: b.outlet_id }))"
            />

            <div class="section-title">User Tujuan</div>
            <div class="nik-row">
                <var-input v-model="receiverNik" placeholder="NIK user" clearable />
                <var-button class="cek-btn" :loading="checking" @click="checkUser">Cek</var-button>
            </div>
            <div v-if="userResult" class="user-result">
                <span class="user-name">{{ userResult.name }}</span>
            </div>

            <div class="section-title">Nominal</div>
            <div class="quick-grid">
                <button v-for="q in QUICK" :key="q" type="button" class="quick-btn" :class="{ active: Number(amount) === q }" @click="amount = String(q)">{{ shortCoin(q) }}</button>
            </div>
            <var-input v-model="amount" type="number" placeholder="Atau isi manual" />

            <label class="form-label">Catatan (opsional)</label>
            <var-input v-model="note" placeholder="Contoh: jual saldo" />

            <var-button class="transfer-btn" block :loading="saving" @click="confirmTransfer">Transfer</var-button>
        </div>
    </div>
</template>

<style scoped>
.transfer {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 8px 0 4px;
}

.nik-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.nik-row .var-input { flex: 1; }

.cek-btn {
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    flex-shrink: 0;
    height: 40px;
}

.user-result {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 700;
    color: #166534;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 8px;
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

.transfer-btn {
    margin-top: 12px;
    background: linear-gradient(135deg, #fb8c00, #f57c00);
    color: #ffffff;
    border-radius: 100px;
    font-weight: 700;
}
</style>