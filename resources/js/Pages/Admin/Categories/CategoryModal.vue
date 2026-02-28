<script setup>
import { ref, watch, onUnmounted, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean, 
    editMode: Boolean, 
    category: Object,
    allCategories: Array // Dùng cho Select box Parent
})

const emit = defineEmits(['close'])

const form = useForm({
    id: null,
    name: '',
    parent_id: '',
    image: null,
    existing_image: null, // Track URL ảnh cũ để không bị xóa khi update
    _method: 'POST'
})

const imagePreview = ref(null)
const customErrors = ref({})

// Lọc bỏ danh mục hiện tại khỏi danh sách "Danh mục cha" để tránh lỗi vòng lặp
const availableParents = computed(() => {
    if (!props.editMode || !props.category) return props.allCategories;
    return props.allCategories.filter(cat => cat.id !== props.category.id);
})

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    customErrors.value = {} 

    if (val) {
        if (props.editMode && props.category) {
            form.id = props.category.id
            form.name = props.category.name
            form.parent_id = props.category.parent_id || ''
            form._method = 'PUT'
            
            // Xử lý ảnh cũ
            imagePreview.value = props.category.image_url;
            form.existing_image = props.category.raw_image;
            form.image = null; 
        } else {
            form.reset(); form.clearErrors();
            form.parent_id = '';
            imagePreview.value = null;
            form.existing_image = null;
            form._method = 'POST'
        }
    }
})

onUnmounted(() => document.body.style.overflow = '')

const handleImageUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.image = file 
        imagePreview.value = URL.createObjectURL(file) 
        customErrors.value.image = null;
    }
}

const clearImage = () => {
    imagePreview.value = null;
    form.image = null;
    form.existing_image = null; // Báo cho server biết là muốn xóa ảnh
    document.getElementById('cat-file-input').value = '';
}

const triggerImageUpload = () => {
    if (!imagePreview.value) {
        document.getElementById('cat-file-input').click();
    }
}

const submitForm = () => {
    customErrors.value = {};
    let hasError = false;

    if (!form.name || !form.name.trim()) {
        customErrors.value.name = 'Vui lòng nhập tên danh mục.';
        hasError = true;
    }

    if (!form.image && !form.existing_image) {
        customErrors.value.image = 'Vui lòng tải lên ảnh đại diện.';
        hasError = true;
    }

    if (hasError) return;

    const url = props.editMode ? route('admin.categories.update', form.id) : route('admin.categories.store')
    
    form.post(url, { 
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => { Object.assign(customErrors.value, errors); }
    })
}

const closeModal = () => emit('close')
</script>

<template>
    <div v-if="show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2 class="title">{{ editMode ? 'CẬP NHẬT DANH MỤC' : 'THÊM DANH MỤC MỚI' }}</h2>
                <button type="button" class="close-btn" @click="closeModal">&times;</button>
            </div>

            <form @submit.prevent="submitForm" class="modal-body">
                <div class="modal-grid">
                    
                    <div class="col-left">
                        <div class="form-group">
                            <label class="label">Tên danh mục <span class="req">*</span></label>
                            <input v-model="form.name" class="input" placeholder="Ví dụ: Giày thể thao...">
                            <span v-if="customErrors.name || form.errors.name" class="err">{{ customErrors.name || form.errors.name }}</span>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label class="label">Danh mục cha (Không bắt buộc)</label>
                            <select v-model="form.parent_id" class="input select-box">
                                <option value="">-- Trống (Đây là danh mục gốc) --</option>
                                <option v-for="cat in availableParents" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.parent_id" class="err">{{ form.errors.parent_id }}</span>
                        </div>
                    </div>

                    <div class="col-right">
                        <label class="label header-label">Ảnh đại diện <span class="req">*</span></label>
                        <span v-if="customErrors.image || form.errors.image" class="err header-error">
                            {{ customErrors.image || form.errors.image }}
                        </span>

                        <div class="img-box" :class="{ 'filled': imagePreview, 'error-border': customErrors.image || form.errors.image }" 
                             @click="triggerImageUpload">
                            
                            <input type="file" id="cat-file-input" hidden accept="image/*" @change="handleImageUpload">

                            <div v-if="!imagePreview" class="placeholder">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span class="tiny-text">Tải ảnh lên</span>  
                            </div>

                            <template v-else>
                                <img :src="imagePreview" />
                                <button type="button" class="btn-remove" @click.stop="clearImage">&times;</button>
                            </template>
                        </div>
                    </div>

                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" @click="closeModal">Hủy bỏ</button>
                <button type="button" class="btn-submit" @click="submitForm" :disabled="form.processing">
                    <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                    {{ form.processing ? 'Đang lưu...' : 'Lưu danh mục' }}
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* CSS Tương tự ProductModal.vue nhưng bỏ bớt các lưới grid phức tạp */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); z-index: 9999; display: flex; justify-content: center; align-items: center; padding: 20px; }
.modal-card { background: #fff; width: 800px; max-width: 95vw; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); display: flex; flex-direction: column; overflow: hidden; animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes popIn { from { transform: scale(0.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-header { padding: 15px 25px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
.title { margin: 0; font-size: 18px; font-weight: 700; color: #0f766e; }
.close-btn { background: none; border: none; font-size: 24px; color: #999; cursor: pointer; } .close-btn:hover { color: #d33; }
.modal-footer { padding: 15px 25px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; background: #fafafa; }
.modal-body { padding: 25px; }

.modal-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 30px; }
.col-left { display: flex; flex-direction: column; }
.col-right { display: flex; flex-direction: column; align-items: center; }

.form-group { display: flex; flex-direction: column; }
.label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
.req { color: red; }
.err { font-size: 11px; color: red; margin-top: 4px; display: block; }
.input { padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; width: 100%; transition: 0.2s; }
.input:focus { border-color: #0f766e; outline: none; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
.select-box { background-color: #fff; cursor: pointer; }

.header-label { margin-bottom: 12px; display: block; text-align: center; width: 100%; }
.header-error { display: block; text-align: center; margin-bottom: 8px; font-size: 12px; }
.img-box { width: 220px; height: 220px; background: #fafafa; border: 2px dashed #d1d5db; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; transition: 0.2s; }
.img-box:hover { border-color: #0f766e; background: #f0fdfa; }
.img-box.filled { border-style: solid; border-color: #e5e7eb; cursor: default; }
.placeholder { text-align: center; color: #aaa; }
.placeholder i { font-size: 32px; margin-bottom: 10px; display: block; }
.tiny-text { font-size: 14px; font-weight: 500; }
.img-box img { width: 100%; height: 100%; object-fit: cover; }
.btn-remove { position: absolute; top: 8px; right: 8px; background: rgba(220, 38, 38, 0.9); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; }

.btn-submit { background: #0f766e; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.btn-submit:hover { background: #115e59; }
.btn-cancel { background: #fff; border: 1px solid #ddd; color: #555; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #f3f4f6; }
.error-border { border-color: red !important; }

@media (max-width: 768px) {
    .modal-overlay { padding: 10px; }
    .modal-card { width: 100%; max-width: 100%; border-radius: 8px; max-height: 95vh; }
    .modal-grid { grid-template-columns: 1fr; gap: 20px; }
    .img-box { width: 100%; height: 250px; }
    .modal-body { padding: 15px; }
    .modal-footer { padding: 15px; }
}
</style>