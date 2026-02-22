<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean, editMode: Boolean, product: Object, categories: Array
})

const emit = defineEmits(['close'])

const form = useForm({
    id: null,
    name: '',
    category_ids: [],
    price: '',
    quantity: '',
    description: '',
    images: [null, null, null, null],
    existing_images: [null, null, null, null],
    clear_model: false,
    model_file: null,
    _method: 'POST'
})

const imagePreviews = ref([null, null, null, null]) // Chứa URL xem trước ảnh 
const modelPreview = ref(null) // Chứa URL xem trước Model 3D

// KHAI BÁO BIẾN NÀY ĐỂ HỨNG LỖI (Sửa lỗi không mở được modal)
const customErrors = ref({})

// --- LOGIC ---
watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    customErrors.value = {} // Reset lỗi mỗi khi mở/đóng modal

    if (val) {
        if (props.editMode && props.product) {
            form.id = props.product.id
            form.name = props.product.name
            form.category_ids = props.product.category_ids || []
            form.price = props.product.price
            form.quantity = props.product.quantity
            form.description = props.product.description || ''
            form._method = 'PUT'

            // Load ảnh cũ
            let loadedPreviews = [null, null, null, null];
            let loadedExisting = [null, null, null, null];

            if (props.product.all_images && props.product.all_images.length > 0) {
                for (let i = 0; i < 4; i++) {
                    if (props.product.all_images[i]) {
                        loadedPreviews[i] = props.product.all_images[i];
                        loadedExisting[i] = props.product.all_images[i]; 
                    }
                }
            } else if (props.product.image) {
                loadedPreviews[0] = props.product.image;
                loadedExisting[0] = props.product.image;
            }

            imagePreviews.value = loadedPreviews;
            form.existing_images = loadedExisting;

            // Load Model 3D cũ
            modelPreview.value = props.product.model_url || null;
            form.model_file = null;
            form.clear_model = false;

        } else {
            // Chế độ thêm mới
            form.reset(); form.clearErrors();
            imagePreviews.value = [null, null, null, null]
            form.existing_images = [null, null, null, null]
            modelPreview.value = null;
            form.clear_model = false;
            form._method = 'POST'
        }
    }
})

onUnmounted(() => document.body.style.overflow = '')

// --- XỬ LÝ UPLOAD ẢNH ---
const handleImageUpload = (e, index) => {
    const file = e.target.files[0]
    if (file) {
        form.images[index] = file 
        imagePreviews.value[index] = URL.createObjectURL(file) 
        customErrors.value['images.0'] = null; // Xóa lỗi thiếu ảnh chính nếu vừa chọn
    }
}

const clearImage = (index) => {
    imagePreviews.value[index] = null;
    form.images[index] = null;
    form.existing_images[index] = null;
    document.getElementById('file-input-' + index).value = '';
}

// --- XỬ LÝ UPLOAD MODEL 3D ---
const handleModelUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (!file.name.toLowerCase().endsWith('.glb')) {
            customErrors.value.model_file = 'Vui lòng chỉ chọn file có định dạng .glb';
            e.target.value = '';
            return;
        }
        form.model_file = file; 
        modelPreview.value = URL.createObjectURL(file); 
        customErrors.value.model_file = null; // Xóa lỗi
    }
}

const clearModel = () => {
    form.model_file = null;
    modelPreview.value = null;
    form.clear_model = true;
    document.getElementById('model-upload').value = '';
}

// --- TRIGGER CLICK ---
const triggerImageUpload = (index) => {
    if (!imagePreviews.value[index]) {
        document.getElementById('file-input-' + index).click();
    }
}

const triggerModelUpload = () => {
    if (!modelPreview.value) {
        document.getElementById('model-upload').click();
    }
}

// --- VALIDATE & SUBMIT FORM ---
const submitForm = () => {
    // 1. Xóa thông báo lỗi cũ
    customErrors.value = {};
    let hasError = false;

    // 2. Validate Tên sản phẩm
    if (!form.name || !form.name.trim()) {
        customErrors.value.name = 'Vui lòng nhập tên sản phẩm.';
        hasError = true;
    }

    // 3. Validate Danh mục
    if (!form.category_ids || form.category_ids.length === 0) {
        customErrors.value.category_ids = 'Vui lòng chọn ít nhất một danh mục.';
        hasError = true;
    }

    // 4. Validate Giá bán
    if (!form.price || form.price < 1000) {
        customErrors.value.price = 'Giá bán tối thiểu phải là 1,000 VNĐ.';
        hasError = true;
    }

    // 5. Xử lý Tồn kho (Mặc định = 0 nếu bỏ trống)
    if (form.quantity === '' || form.quantity === null) {
        form.quantity = 0;
    } else if (form.quantity < 0) {
        customErrors.value.quantity = 'Tồn kho không được nhỏ hơn 0.';
        hasError = true;
    }

    // 6. Validate Ảnh chính (Bắt buộc ô số 1 phải có ảnh)
    if (!form.images[0] && !form.existing_images[0]) {
        customErrors.value['images.0'] = 'Sản phẩm bắt buộc phải có ảnh chính (Ảnh 1).';
        hasError = true;
    }

    // 7. Validate Model 3D
    if (!props.editMode && !form.model_file) {
        // Thêm mới: Bắt buộc chọn file
        customErrors.value.model_file = 'Vui lòng tải lên file mô hình 3D (.glb).';
        hasError = true;
    } else if (props.editMode && form.clear_model && !form.model_file) {
        // Cập nhật: Nếu xóa file cũ thì bắt buộc phải up file mới
        customErrors.value.model_file = 'Sản phẩm bắt buộc phải có file mô hình 3D.';
        hasError = true;
    }

    // Nếu có lỗi thì dừng lại không gửi form
    if (hasError) return;

    // Tiến hành gửi dữ liệu
    const url = props.editMode ? route('admin.products.update', form.id) : route('admin.products.store')
    
    form.post(url, { 
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => {
            // Hứng lỗi từ server nếu có
            Object.assign(customErrors.value, errors);
        }
    })
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
                            <input v-model="form.name" class="input" placeholder="Nhập tên sản phẩm...">
                            <span v-if="customErrors.name || form.errors.name" class="err">{{ customErrors.name || form.errors.name }}</span>
                        </div>

                        <div class="form-group">
                            <label class="label">Danh mục (Có thể chọn nhiều) <span class="req">*</span></label>
                            <div class="input category-list" :class="{ 'error-border': customErrors.category_ids || form.errors.category_ids }">
                                <label v-for="cat in categories" :key="cat.id" class="checkbox-label">
                                    <input type="checkbox" v-model="form.category_ids" :value="cat.id">
                                    {{ cat.name }}
                                </label>
                            </div>
                            <span v-if="customErrors.category_ids || form.errors.category_ids" class="err">{{ customErrors.category_ids || form.errors.category_ids }}</span>
                        </div>

                        <div class="row-group">
                            <div class="form-group">
                                <label class="label">Giá bán (VNĐ) <span class="req">*</span></label>
                                <input v-model="form.price" type="number" class="input" min="0" placeholder="VD: 150000">
                                <span v-if="customErrors.price || form.errors.price" class="err">{{ customErrors.price || form.errors.price }}</span>
                            </div>
                            <div class="form-group">
                                <label class="label">Tồn kho</label>
                                <input v-model="form.quantity" type="number" class="input" placeholder="Mặc định: 0" min="0">
                                <span v-if="customErrors.quantity || form.errors.quantity" class="err">{{ customErrors.quantity || form.errors.quantity }}</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label">Mô tả chi tiết</label>
                            <textarea v-model="form.description" class="input textarea" placeholder="Nhập mô tả sản phẩm..."></textarea>
                        </div>
                    </div>

                    <div class="col-right">

                        <label class="label header-label">Hình ảnh sản phẩm (Tối đa 4 ảnh) <span class="req">*</span></label>
                        <span v-if="customErrors['images.0'] || form.errors['images.0'] || form.errors['existing_images.0']" class="err header-error">
                            {{ customErrors['images.0'] || form.errors['images.0'] || form.errors['existing_images.0'] }}
                        </span>

                        <div class="img-grid">
                            <div v-for="(img, index) in 4" :key="index" class="img-box"
                                :class="{ 
                                    'filled': imagePreviews[index], 
                                    'error-border': index === 0 && (customErrors['images.0'] || form.errors['images.0'] || form.errors['existing_images.0']) 
                                }"
                                @click="triggerImageUpload(index)">

                                <input type="file" :id="'file-input-' + index" hidden accept="image/*" @change="handleImageUpload($event, index)">

                                <div v-if="!imagePreviews[index]" class="placeholder">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span class="tiny-text">Ảnh {{ index + 1 }}</span>
                                </div>

                                <template v-else>
                                    <img :src="imagePreviews[index]" />
                                    <button type="button" class="btn-remove" @click.stop="clearImage(index)">&times;</button>
                                    <div v-if="index === 0" class="badge-main">Ảnh chính</div>
                                </template>
                            </div>
                        </div>

                        <div class="model-section">
                            <label class="label header-label" style="margin-top: 20px;">Mô hình 3D (.glb) <span class="req">*</span></label>
                            <span v-if="customErrors.model_file || form.errors.model_file" class="err header-error">
                                {{ customErrors.model_file || form.errors.model_file }}
                            </span>

                            <div class="model-box"
                                :class="{ 'has-model': modelPreview, 'error-border': customErrors.model_file || form.errors.model_file }"
                                @click="triggerModelUpload">

                                <input type="file" id="model-upload" hidden accept=".glb" @change="handleModelUpload">

                                <div v-if="!modelPreview" class="placeholder model-placeholder">
                                    <i class="fa-solid fa-cube"></i>
                                    <span>Tải lên file 3D (.glb)</span>
                                </div>

                                <template v-else>
                                    <button type="button" class="btn-remove model-remove" @click.stop="clearModel">&times;</button>

                                    <model-viewer :src="modelPreview" auto-rotate camera-controls touch-action="pan-y"
                                        style="width: 100%; height: 100%; background-color: #f1f1f1;">
                                    </model-viewer>
                                </template>
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
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.modal-card {
    background: #fff;
    width: 1100px;
    max-width: 95vw;
    height: auto;
    max-height: 85vh;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes popIn {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* --- HEADER & FOOTER --- */
.modal-header {
    padding: 15px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
}

.title { margin: 0; font-size: 18px; font-weight: 700; color: #0f766e; }
.close-btn { background: none; border: none; font-size: 24px; color: #999; cursor: pointer; }
.close-btn:hover { color: #d33; }

.modal-footer {
    padding: 15px 25px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; background: #fafafa;
}

/* --- BODY --- */
.modal-body { flex: 1; overflow-y: auto; padding: 25px; }

/* --- GRID LAYOUT --- */
.modal-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
.col-left { display: flex; flex-direction: column; gap: 15px; }
.col-right { background: #f8fcfb; border: 1px dashed #bce3db; border-radius: 10px; padding: 20px; height: fit-content; }

/* FORM ELEMENTS */
.row-group { display: flex; gap: 10px; }
.form-group { flex: 1; display: flex; flex-direction: column; }
.label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
.req { color: red; }
.err { font-size: 11px; color: red; margin-top: 2px; }

.input {
    padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; width: 100%; transition: 0.2s;
}

.input:focus { border-color: #0f766e; outline: none; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
.textarea { height: 100px; resize: none; }

/* CHECKBOX DANH MỤC */
.category-list {
    height: 100px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; background: #fff; padding: 10px; border-color: #ddd;
}
.category-list.error-border { border: 1px solid red !important; }
.checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: #333; }
.checkbox-label input[type="checkbox"] { width: 16px; height: 16px; accent-color: #0f766e; cursor: pointer; }

/* ẢNH GRID */
.header-label { margin-bottom: 12px; display: block; text-align: center; }
.img-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
.img-box {
    aspect-ratio: 1; background: #fff; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; transition: 0.2s;
}
.img-box:hover { border-color: #0f766e; background: #f0fdfa; }
.img-box.filled { border-style: solid; border-color: #e5e7eb; cursor: default; }

.placeholder { text-align: center; color: #aaa; }
.placeholder i { font-size: 24px; margin-bottom: 5px; display: block; }
.tiny-text { font-size: 12px; }
.img-box img { width: 100%; height: 100%; object-fit: cover; }
.btn-remove {
    position: absolute; top: 4px; right: 4px; background: rgba(220, 38, 38, 0.9); color: white; border: none; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10;
}
.badge-main { position: absolute; bottom: 0; width: 100%; background: #0f766e; color: white; font-size: 10px; text-align: center; padding: 2px 0; pointer-events: none; }

/* MODEL 3D */
.model-section { margin-top: 20px; }
.model-box {
    background: #fff; border: 2px dashed #ccc; border-radius: 8px; height: 70px; display: flex; align-items: center; justify-content: center; color: #666; cursor: pointer; position: relative; overflow: hidden; transition: 0.2s;
}
.model-box:hover { border-color: #0f766e; background: #f0fdfa; }
.model-box.has-model { height: 250px; border-style: solid; border-color: #e5e7eb; background: #f1f1f1; cursor: default; }
.model-placeholder { display: flex; gap: 10px; align-items: center; }
.model-placeholder i { font-size: 20px; margin: 0; }
.model-remove { top: 8px; right: 8px; width: 28px; height: 28px; font-size: 16px; }

/* BUTTONS & LỖI */
.btn-submit {
    background: #0f766e; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;
}
.btn-submit:hover { background: #115e59; transform: translateY(-1px); }
.btn-cancel { background: #fff; border: 1px solid #ddd; color: #555; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #f3f4f6; }

/* Thêm style cho viền đỏ báo lỗi */
.error-border { border-color: red !important; }
.header-error { display: block; text-align: center; margin-bottom: 8px; font-size: 12px; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .modal-card { width: 100%; height: 100vh; max-height: none; border-radius: 0; }
    .modal-grid { grid-template-columns: 1fr; }
    .img-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>