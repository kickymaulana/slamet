<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import { searchState } from '../../composables/search';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    items: {
        data: Array<any>;
        current_page: number;
        last_page: number;
    };
    today: string;
}>();

const listData = ref([...props.items.data]);
const currentPage = ref(props.items.current_page);
const loading = ref(false);
const isRefreshing = ref(false);
const finished = ref(props.items.current_page >= props.items.last_page);

const filteredItems = computed(() => {
    if (!searchState.value) return listData.value;
    const q = searchState.value.toLowerCase();
    return listData.value.filter((i) => i.name.toLowerCase().includes(q) || (i.category?.name ?? '').toLowerCase().includes(q));
});

const loadMore = () => {
    if (finished.value || loading.value) return;
    loading.value = true;
    router.get(route('items.index', { page: currentPage.value + 1 }), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['items'],
        onSuccess: (page) => {
            const items = page.props.items as any;
            listData.value.push(...items.data);
            currentPage.value = items.current_page;
            finished.value = currentPage.value >= items.last_page;
            loading.value = false;
        },
        onError: () => { loading.value = false; },
    });
};

const refresh = () => {
    isRefreshing.value = true;
    router.get(route('items.index'), {}, {
        preserveState: false,
        replace: true,
        only: ['items'],
        onSuccess: (page) => {
            const items = page.props.items as any;
            listData.value = [...items.data];
            currentPage.value = items.current_page;
            finished.value = currentPage.value >= items.last_page;
            isRefreshing.value = false;
        },
        onError: () => { isRefreshing.value = false; },
    });
};

const coin = (n: number) => n.toLocaleString('id-ID') + ' Coin';
</script>

<template>
    <var-pull-refresh v-model="isRefreshing" @refresh="refresh">
        <var-list
            v-model:loading="loading"
            :finished="finished"
            loading-text="Memuat..."
            finished-text="Semua menu sudah dimuat"
            @load="loadMore"
        >
            <div v-if="filteredItems.length === 0 && !loading" class="empty">
                Belum ada menu.
            </div>

            <div v-for="i in filteredItems" :key="i.id" class="row-card">
                <Link :href="route('items.edit', { item: i.id })" class="row-link">
                    <div class="item-photo">
                        <img
                            v-if="i.photo"
                            :src="route('items.foto', { item: i.id })"
                            :alt="i.name"
                            loading="lazy"
                        />
                        <var-icon v-else name="image-outline" :size="22" color="#cbd5e1" />
                    </div>
                    <div class="row-info">
                        <span class="name">{{ i.name }}</span>
                        <span class="meta">
                            {{ i.outlet?.name }} • {{ i.category?.name }} • {{ coin(i.price) }}
                            <var-chip v-if="i.stock_date === props.today" size="mini" round :type="i.stock > 0 ? 'success' : 'danger'">
                                {{ i.stock > 0 ? 'Stok '+i.stock : 'Habis' }}
                            </var-chip>
                        </span>
                    </div>
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
    gap: 12px;
    padding: 12px 14px;
    text-decoration: none;
    color: inherit;
}

.item-photo {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.item-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.row-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
</style>