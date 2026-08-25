<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const boundOutlet = computed(() => page.props.auth?.user?.outlet_id ?? null);

const props = defineProps<{
    items: Array<{
        id: number;
        name: string;
        photo: string | null;
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
    stocks[i.id] = i.stock_date === props.today ? String(i.stock) : '';
});

const saving = ref(false);
const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';

const showPhotoViewer = ref(false);
const currentPhotoUrl = ref('');
const currentPhotoName = ref('');

const openPhotoViewer = (url: string, name: string) => {
    currentPhotoUrl.value = url;
    currentPhotoName.value = name;
    showPhotoViewer.value = true;
};

const closePhotoViewer = () => { showPhotoViewer.value = false; };

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

        <p class="date-note">Tanggal: {{ today }}</p>

        <div v-if="items.length === 0" class="empty">Belum ada menu aktif.</div>

        <div v-for="i in items" :key="i.id" class="stock-row">
            <div class="stock-photo" style="cursor: zoom-in" @click="i.photo && openPhotoViewer(route('items.foto', { item: i.id }), i.name)">
                <img
                    v-if="i.photo"
                    :src="route('items.foto', { item: i.id })"
                    :alt="i.name"
                    loading="lazy"
                />
                <var-icon v-else name="image-outline" :size="20" color="#cbd5e1" />
            </div>
            <div class="stock-info">
                <span class="stock-name">{{ i.name }}</span>
                <span class="stock-price">{{ coin(i.price) }}</span>
            </div>
            <var-input
                v-model="stocks[i.id]"
                type="number"
                placeholder="Stok"
                class="stock-input"
            />
        </div>

        <var-button class="save-btn" block :loading="saving" @click="save">
            Simpan Stok Hari Ini
        </var-button>

        <!-- Photo Viewer Overlay -->
        <Transition name="fade">
            <div v-if="showPhotoViewer" class="photo-overlay" @click="closePhotoViewer">
                <div class="photo-title">{{ currentPhotoName }}</div>
                <img :src="currentPhotoUrl" alt="preview" class="photo-image" @click.stop />
            </div>
        </Transition>
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

.stock-photo {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.stock-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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

/* Photo viewer overlay */
.photo-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.photo-title {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}

.photo-image {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>