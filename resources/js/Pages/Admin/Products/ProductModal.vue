<script setup>
// ... (Giữ nguyên phần script setup không đổi) ...
import { ref, watch, onUnmounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean, editMode: Boolean, product: Object, categories: Array
})

const emit = defineEmits(['close'])

const form = useForm({
    id: null, name: '', category_id: '', price: '', quantity: '', description: '',
    images: [null, null, null, null], model_file: null, _method: 'POST'
})

const imagePreviews = ref([null, null, null, null])

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    if (val) {
        if (props.editMode && props.product) {
            form.id = props.product.id
            form.name = props.product.name
            form.category_id = props.product.category_id
            form.price = props.product.price
            form.quantity = props.product.quantity
            form.description = props.product.description || ''
            form._method = 'PUT'
            imagePreviews.value = [props.product.image, null, null, null]
        } else {
            form.reset(); form.clearErrors();
            imagePreviews.value = [null, null, null, null]
            form._method = 'POST'
        }
    }
})

onUnmounted(() => document.body.style.overflow = '')

const handleImageUpload = (e, index) => {
    const file = e.target.files[0]
    if (file) {
        form.images[index] = file
        imagePreviews.value[index] = URL.createObjectURL(file)
    }
}

const submitForm = () => {
    const url = props.editMode ? route('admin.products.update', form.id) : route('admin.products.store')
    form.post(url, { onSuccess: () => closeModal(), preserveScroll: true })
}

const closeModal = () => emit('close')
</script>

<template>
    <div v-if="show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-card">
            
            <div class="modal-header">
                <h2 class="title">{{ editMode ? 'CẬP NHẬT SẢN PHẨM' : 'THÊM SẢN PHẨM MỚI' }}</h2>
                <button type="button" class="close-btn" @click="closeModal">&times;</button>
            </div>

            <form @submit.prevent="submitForm" class="modal-body">
                <div class="modal-grid">
                    
                    <div class="col-left">
                        <div class="form-group">
                            <label class="label">Tên sản phẩm <span class="req">*</span></label>
                            <input v-model="form.name" class="input" placeholder="Nhập tên sản phẩm..." required>
                            <span v-if="form.errors.name" class="err">{{ form.errors.name }}</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="label">Danh mục <span class="req">*</span></label>
                            <select v-model="form.category_id" class="input" required>
                                <option value="">-- Chọn danh mục --</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div class="row-group">
                            <div class="form-group">
                                <label class="label">Giá bán <span class="req">*</span></label>
                                <input v-model="form.price" type="number" class="input" required>
                            </div>
                            <div class="form-group">
                                <label class="label">Tồn kho <span class="req">*</span></label>
                                <input v-model="form.quantity" type="number" class="input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label">Mô tả chi tiết</label>
                            <textarea v-model="form.description" class="input textarea" placeholder="Mô tả..."></textarea>
                        </div>
                    </div>

                    <div class="col-right">
                        <label class="label header-label">Hình ảnh sản phẩm (Tối đa 4)</label>
                        
                        <div class="img-grid">
                            <div v-for="(img, index) in 4" :key="index" 
                                 class="img-box" 
                                 :class="{ 'filled': imagePreviews[index] }"
                                 @click="document.getElementById('file-input-'+index).click()">
                                
                                <input type="file" :id="'file-input-'+index" hidden accept="image/*" @change="handleImageUpload($event, index)">
                                
                                <div v-if="!imagePreviews[index]" class="placeholder">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span class="tiny-text">Ảnh {{ index + 1 }}</span>
                                </div>

                                <img v-else :src="imagePreviews[index]" />
                                
                                <button v-if="imagePreviews[index]" class="btn-remove" 
                                        @click.stop="imagePreviews[index] = null; form.images[index] = null">
                                    &times;
                                </button>
                                <div v-if="index === 0" class="badge-main">Ảnh chính</div>
                            </div>
                        </div>

                        <div class="model-section">
                            <label class="label">File 3D (.glb)</label>
                            <div class="model-box">
                                <i class="fa-solid fa-cube"></i>
                                <span>Tải lên mô hình 3D</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" @click="closeModal">Hủy bỏ</button>
                <button type="button" class="btn-submit" @click="submitForm" :disabled="form.processing">
                    <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                    {{ form.processing ? 'Đang lưu...' : 'Lưu sản phẩm' }}
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* --- CONTAINER --- */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px);
    z-index: 9999;
    display: flex; justify-content: center; align-items: center;
    padding: 20px;
}

.modal-card {
    background: #fff;
    width: 1100px;
    max-width: 95vw;
    /* Chiều cao tự động nhưng max 85vh để tránh tràn màn hình */
    height: auto; max-height: 85vh; 
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    display: flex; flex-direction: column;
    overflow: hidden;
    animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes popIn {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* --- HEADER & FOOTER --- */
.modal-header {
    padding: 15px 25px; border-bottom: 1px solid #f0f0f0;
    display: flex; justify-content: space-between; align-items: center;
    background: #fff;
}
.title { margin: 0; font-size: 18px; font-weight: 700; color: #0f766e; }
.close-btn { background: none; border: none; font-size: 24px; color: #999; cursor: pointer; }
.close-btn:hover { color: #d33; }

.modal-footer {
    padding: 15px 25px; border-top: 1px solid #f0f0f0;
    display: flex; justify-content: flex-end; gap: 12px;
    background: #fafafa;
}

/* --- BODY --- */
.modal-body {
    flex: 1; overflow-y: auto; padding: 25px;
}

/* --- GRID LAYOUT (THAY ĐỔI CHÍNH Ở ĐÂY) --- */
.modal-grid {
    display: grid;
    /* Cột trái 1 phần - Cột phải 2 phần */
    grid-template-columns: 1fr 2fr; 
    gap: 30px;
}

/* CỘT TRÁI (Nhỏ) */
.col-left { display: flex; flex-direction: column; gap: 15px; }

/* CỘT PHẢI (To) */
.col-right {
    background: #f8fcfb; border: 1px dashed #bce3db;
    border-radius: 10px; padding: 20px;
    height: fit-content;
}

/* FORM ELEMENTS */
.row-group { display: flex; gap: 10px; } /* Giá & Tồn kho nằm ngang */
.form-group { flex: 1; display: flex; flex-direction: column; }
.label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
.req { color: red; }
.err { font-size: 11px; color: red; margin-top: 2px; }

.input {
    padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 14px; width: 100%; transition: 0.2s;
}
.input:focus { border-color: #0f766e; outline: none; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
.textarea { height: 120px; resize: none; }

/* ẢNH GRID (THAY ĐỔI ĐỂ PHÙ HỢP CỘT TO) */
.img-grid {
    display: grid; 
    grid-template-columns: repeat(4, 1fr); /* 4 ảnh nằm ngang */
    gap: 15px;
}

.img-box {
    aspect-ratio: 1; /* Luôn vuông */
    background: #fff; border: 2px dashed #d1d5db; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; position: relative; overflow: hidden;
    transition: 0.2s;
}
.img-box:hover { border-color: #0f766e; background: #f0fdfa; }

.placeholder { text-align: center; color: #aaa; }
.placeholder i { font-size: 24px; margin-bottom: 5px; display: block; } /* Icon to hơn */
.tiny-text { font-size: 12px; }

.img-box img { width: 100%; height: 100%; object-fit: cover; }

.btn-remove {
    position: absolute; top: 4px; right: 4px;
    background: rgba(220, 38, 38, 0.9); color: white; border: none;
    border-radius: 50%; width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
}

.badge-main {
    position: absolute; bottom: 0; width: 100%;
    background: #0f766e; color: white; font-size: 10px;
    text-align: center; padding: 2px 0;
}

.model-section { margin-top: 20px; }
.model-box {
    background: #fff; border: 2px dashed #ccc; border-radius: 8px;
    height: 60px; display: flex; align-items: center; justify-content: center;
    gap: 10px; color: #666; font-size: 14px; font-weight: 500; cursor: pointer;
}

/* BUTTONS */
.btn-submit {
    background: #0f766e; color: white; border: none;
    padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 8px; transition: 0.2s;
}
.btn-submit:hover { background: #115e59; transform: translateY(-1px); }

.btn-cancel {
    background: #fff; border: 1px solid #ddd; color: #555;
    padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;
}
.btn-cancel:hover { background: #f3f4f6; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .modal-card { width: 100%; height: 100vh; max-height: none; border-radius: 0; }
    .modal-grid { grid-template-columns: 1fr; } /* Về 1 cột */
    .img-grid { grid-template-columns: repeat(2, 1fr); } /* 2 ảnh/hàng trên mobile */
}
</style>