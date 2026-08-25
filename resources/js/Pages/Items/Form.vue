<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Snackbar, Dialog } from '@varlet/ui';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    item: Record<string, any> | null;
    categories: Array<{ id: number; name: string }>;
    outlets: Array<{ id: number; name: string }>;
    bound_outlet: number | null;
}>();

const isEditing = !!props.item;
const form = reactive<Record<string, any>>({
    outlet_id: props.bound_outlet ?? props.item?.outlet_id ?? props.outlets[0]?.id ?? '',
    category_id: props.item?.category_id ?? '',
    name: props.item?.name ?? '',
    description: props.item?.description ?? '',
    price: props.item?.price ?? 0,
    is_active: props.item?.is_active ?? true,
});
const saving = ref(false);
const photoFile = ref<File | null>(null);
const photoPreview = ref(props.item?.photo ? route('items.foto', { item: props.item.id }) : '');
const photoLoading = ref(false);

const showPhotoViewer = ref(false);
const openPhotoViewer = () => { if (photoPreview.value) showPhotoViewer.value = true; };
const closePhotoViewer = () => { showPhotoViewer.value = false; };

const compressImage = (file: File, maxSize = 1200, quality = 0.75): Promise<File> =>
    new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, maxSize / Math.max(img.width, img.height));
            const w = Math.round(img.width * scale);
            const h = Math.round(img.height * scale);
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                URL.revokeObjectURL(url);
                reject(new Error('no ctx'));
                return;
            }
            ctx.drawImage(img, 0, 0, w, h);
            URL.revokeObjectURL(url);
            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error('compress fail'));
                    return;
                }
                resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', quality);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('load fail'));
        };
        img.src = url;
    });

const onPhotoChange = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    if (!/^image\//.test(file.type)) {
        Snackbar.warning('File harus berupa gambar');
        target.value = '';
        return;
    }
    photoLoading.value = true;
    try {
        const compressed = await compressImage(file, 1200, 0.75);
        photoFile.value = compressed;
        if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
        photoPreview.value = URL.createObjectURL(compressed);
    } catch {
        Snackbar.error('Gagal memproses gambar');
    } finally {
        photoLoading.value = false;
        target.value = '';
    }
};

const submit = () => {
    saving.value = true;
    const fd = new FormData();
    Object.entries(form).forEach(([k, v]) => fd.append(k, v === true ? '1' : v === false ? '0' : String(v ?? '')));
    if (photoFile.value) fd.append('photo', photoFile.value);

    const opts = {
        headers: { 'Content-Type': 'multipart/form-data' },
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; Snackbar.error('Gagal menyimpan. Periksa input.'); },
    };

    if (isEditing) {
        fd.append('_method', 'PUT');
        router.post(route('items.update', { item: props.item!.id }), fd, opts);
    } else {
        router.post(route('items.store'), fd, opts);
    }
};

const remove = () => {
    Dialog({
        title: 'Hapus Menu',
        message: `Yakin ingin menghapus "${props.item?.name}"?`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('items.destroy', { item: props.item!.id }), {
                onError: () => Snackbar.error('Gagal menghapus.'),
            });
        },
    });
};
</script>

<template>
    <div class="white-card">
        <h2 class="page-title">{{ isEditing ? 'Edit Menu' : 'Tambah Menu' }}</h2>
        <var-space direction="column" size="small">
            <label v-if="!bound_outlet" class="form-label">Kantin</label>
            <var-select
                v-if="!bound_outlet"
                v-model="form.outlet_id"
                placeholder="Pilih kantin"
                :options="outlets.map((o) => ({ label: o.name, value: o.id }))"
            />
            <label class="form-label">Kategori</label>
            <var-select
                v-model="form.category_id"
                placeholder="Pilih kategori"
                :options="categories.map((c) => ({ label: c.name, value: c.id }))"
            />
            <label class="form-label">Nama Menu</label>
            <var-input v-model="form.name" placeholder="Contoh: Ayam Bakar" />
            <label class="form-label">Deskripsi (opsional)</label>
            <var-input v-model="form.description" :textarea="true" placeholder="Bahan & keterangan" />
            <label class="form-label">Harga (Coin)</label>
            <var-input v-model="form.price" type="number" placeholder="Contoh: 8000" />

            <div class="field-row">
                <label class="form-label">Foto {{ photoLoading ? '(memproses...)' : '' }}</label>
                <input type="file" accept="image/*" @change="onPhotoChange" :disabled="photoLoading" />
                <div class="photo-box" @click="openPhotoViewer">
                    <img v-if="photoPreview" :src="photoPreview" class="photo-preview" alt="Preview foto" />
                    <template v-else>
                        <var-icon name="image-outline" :size="48" color="#cbd5e1" />
                        <span class="photo-empty">Belum ada foto. Klik pilih file di atas.</span>
                    </template>
                </div>
            </div>
        </var-space>

        <div class="form-actions">
            <var-button v-if="isEditing" type="danger" block @click="remove" class="delete-btn">
                Hapus Menu
            </var-button>
            <var-button type="primary" block :loading="saving" @click="submit">
                {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
            </var-button>
        </div>

        <!-- Photo Viewer Overlay -->
        <Transition name="fade">
            <div v-if="showPhotoViewer" class="photo-overlay" @click="closePhotoViewer">
                <div class="photo-title">{{ form.name || 'Foto Menu' }}</div>
                <img :src="photoPreview" alt="preview" class="photo-image" @click.stop />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.field-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.photo-box {
    width: 200px;
    height: 200px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: zoom-in;
    overflow: hidden;
}

.photo-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-empty {
    font-size: 12px;
    color: #94a3b8;
    text-align: center;
    padding: 0 8px;
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

.form-actions {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.delete-btn {
    border-radius: 100px;
}
</style>