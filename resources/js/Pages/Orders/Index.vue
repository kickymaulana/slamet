<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    orders: {
        data: Array<any>;
        current_page: number;
        last_page: number;
    };
}>();

const listData = ref([...props.orders.data]);
const currentPage = ref(props.orders.current_page);
const loading = ref(false);
const isRefreshing = ref(false);
const finished = ref(props.orders.current_page >= props.orders.last_page);

const filteredOrders = computed(() => {
    if (!searchState.value) return listData.value;
    const q = searchState.value.toLowerCase();
    return listData.value.filter((o) =>
        (o.nota_code.toLowerCase().includes(q) || (o.user?.name ?? '').toLowerCase().includes(q)),
    );
});

type ChipType = 'default' | 'primary' | 'info' | 'success' | 'warning' | 'danger';

const statusChip: Record<string, ChipType> = { pending: 'warning', paid: 'success', cancelled: 'danger' };
const statusLabel: Record<string, string> = { pending: 'BELUM BAYAR', paid: 'LUNAS', cancelled: 'BATAL' };

const rupiah = (n: number) => 'Rp ' + n.toLocaleString('id-ID');

const loadMore = () => {
    if (finished.value || loading.value) return;
    loading.value = true;
    router.get(route('orders.index', { page: currentPage.value + 1 }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['orders'],
        onSuccess: (page) => {
            const orders = page.props.orders as any;
            listData.value.push(...orders.data);
            currentPage.value = orders.current_page;
            finished.value = currentPage.value >= orders.last_page;
            loading.value = false;
        },
        onError: () => { loading.value = false; },
    });
};

const refresh = () => {
    isRefreshing.value = true;
    router.get(route('orders.index'), {}, {
        preserveState: false,
        replace: true,
        only: ['orders'],
        onSuccess: (page) => {
            const orders = page.props.orders as any;
            listData.value = [...orders.data];
            currentPage.value = orders.current_page;
            finished.value = currentPage.value >= orders.last_page;
            isRefreshing.value = false;
        },
        onError: () => { isRefreshing.value = false; },
    });
};
</script>

<template>
    <var-pull-refresh v-model="isRefreshing" @refresh="refresh">
        <var-list
            v-model:loading="loading"
            :finished="finished"
            loading-text="Memuat..."
            finished-text="Semua pesanan sudah dimuat"
            @load="loadMore"
        >
            <div v-if="filteredOrders.length === 0 && !loading" class="empty">
                Belum ada pesanan.
            </div>

            <div v-for="o in filteredOrders" :key="o.id" class="row-card">
                <Link :href="route('orders.show', { order: o.id })" class="row-link">
                    <div class="row-info">
                        <div class="row-top">
                            <span class="request-code">{{ o.nota_code }}</span>
                            <var-chip :type="statusChip[o.status] ?? 'default'" size="small" round>
                                {{ statusLabel[o.status] ?? o.status }}
                            </var-chip>
                        </div>
                        <div class="row-meta">
                            <span>{{ o.user?.name }}</span>
                            <span>{{ o.outlet?.name }}</span>
                            <span>{{ rupiah(o.total_amount) }}</span>
                            <span>{{ o.created_at }}</span>
                        </div>
                    </div>
                    <var-icon name="chevron-right" :size="20" color="#cbd5e1" />
                </Link>
            </div>
        </var-list>
    </var-pull-refresh>
</template>

<style scoped>
.empty {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.row-card {
    background: #ffffff;
    border-radius: 12px;
    margin-bottom: 8px;
    border: 1px solid #f1f5f9;
}

.row-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    text-decoration: none;
    color: inherit;
}

.row-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.row-top {
    display: flex;
    align-items: center;
    gap: 8px;
}

.request-code {
    font-family: monospace;
    font-weight: 800;
    color: #0f172a;
    font-size: 13px;
}

.row-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}
</style>
