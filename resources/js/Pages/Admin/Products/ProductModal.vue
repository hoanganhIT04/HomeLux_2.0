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

const imagePreviews = ref([null, null, null, null]) // Chứa URL để xem trước ảnh 
const modelPreview = ref(null) // Chứa URL để xem trước Model 3D

// --- LOGIC ---
watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    if (val) {
        if (props.editMode && props.product) {
            form.id = props.product.id
            form.name = props.product.name
            form.category_ids = props.product.category_ids || [] 
            form.price = props.product.price
            form.quantity = props.product.quantity
            form.description = props.product.description || ''
            form._method = 'PUT'
            
            // 2. (SỬA Ở ĐÂY) Load ảnh cũ vào form.existing_images
            let loadedPreviews = [null, null, null, null];
            let loadedExisting = [null, null, null, null];
            
            if (props.product.all_images && props.product.all_images.length > 0) {
                for (let i = 0; i < 4; i++) {
                    if (props.product.all_images[i]) {
                        loadedPreviews[i] = props.product.all_images[i];
                        loadedExisting[i] = props.product.all_images[i]; // Giữ URL để nộp lên backend
                    }
                }
            } else if (props.product.image) {
                loadedPreviews[0] = props.product.image;
                loadedExisting[0] = props.product.image;
            }
            
            imagePreviews.value = loadedPreviews;
            form.existing_images = loadedExisting;
            
            modelPreview.value = props.product.model_url || null;
            form.model_file = null;
            form.clear_model = false; // Reset trạng thái xóa model

        } else {
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
        form.images[index] = file // Gắn file thực tế vào form để post
        imagePreviews.value[index] = URL.createObjectURL(file) // Tạo link ảo để preview
    }
}

const clearImage = (index) => {
    imagePreviews.value[index] = null;
    form.images[index] = null;
    form.existing_images[index] = null; 
    document.getElementById('file-input-' + index).value = '';
}

const clearModel = () => {
    form.model_file = null;
    modelPreview.value = null;
    form.clear_model = true; 
    document.getElementById('model-upload').value = '';
}

// --- XỬ LÝ UPLOAD MODEL 3D ---
const handleModelUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        // Kiểm tra đúng định dạng .glb
        if (!file.name.toLowerCase().endsWith('.glb')) {
            alert('Vui lòng chỉ chọn file có định dạng .glb');
            e.target.value = '';
            return;
        }
        form.model_file = file; // Gắn file vào form
        modelPreview.value = URL.createObjectURL(file); // Tạo link ảo để chạy <model-viewer>
    }
}



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

// --- SUBMIT FORM ---
const submitForm = () => {
    const url = props.editMode ? route('admin.products.update', form.id) : route('admin.products.store')
    
    // inertia useForm tự động xử lý FormData nếu phát hiện có File objects (như form.images, form.model_file)
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
                            <label class="label">Danh mục (Có thể chọn nhiều) <span class="req">*</span></label>
                            <div class="input category-list">
                                <label v-for="cat in categories" :key="cat.id" class="checkbox-label">
                                    <input type="checkbox" v-model="form.category_ids" :value="cat.id">
                                    {{ cat.name }}
                                </label>
                            </div>
                            <span v-if="form.errors.category_ids" class="err">{{ form.errors.category_ids }}</span>
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
                        
                        <label class="label header-label">Hình ảnh sản phẩm (Tối đa 4 ảnh)</label>
                        <div class="img-grid">
                            <div v-for="(img, index) in 4" :key="index" 
                                 class="img-box" 
                                 :class="{ 'filled': imagePreviews[index] }"
                                 @click="triggerImageUpload(index)">
                                
                                <input type="file" :id="'file-input-'+index" hidden accept="image/*" @change="handleImageUpload($event, index)">
                                
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
                            <label class="label header-label" style="margin-top: 20px;">Mô hình 3D (.glb)</label>
                            
                            <div class="model-box" 
                                 :class="{ 'has-model': modelPreview }"
                                 @click="triggerModelUpload">
                                
                                <input type="file" id="model-upload" hidden accept=".glb" @change="handleModelUpload">
                                
                                <div v-if="!modelPreview" class="placeholder model-placeholder">
                                    <i class="fa-solid fa-cube"></i>
                                    <span>Tải lên file 3D (.glb)</span>
                                </div>

                                <template v-else>
                                    <button type="button" class="btn-remove model-remove" @click.stop="clearModel">&times;</button>
                                    
                                    <model-viewer 
                                        :src="modelPreview" 
                                        auto-rotate 
                                        camera-controls 
                                        touch-action="pan-y"
                                        style="width: 100%; height: 100%; background-color: #f1f1f1;">
                                    </model-viewer>
                                </template>
                            </div>
                            <span v-if="form.errors.model_file" class="err">{{ form.errors.model_file }}</span>
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
    background: #fff; width: 1100px; max-width: 95vw;
    height: auto; max-height: 85vh; border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    display: flex; flex-direction: column; overflow: hidden;
    animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes popIn {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* --- HEADER & FOOTER --- */
.modal-header {
    padding: 15px 25px; border-bottom: 1px solid #f0f0f0;
    display: flex; justify-content: space-between; align-items: center; background: #fff;
}
.title { margin: 0; font-size: 18px; font-weight: 700; color: #0f766e; }
.close-btn { background: none; border: none; font-size: 24px; color: #999; cursor: pointer; }
.close-btn:hover { color: #d33; }

.modal-footer {
    padding: 15px 25px; border-top: 1px solid #f0f0f0;
    display: flex; justify-content: flex-end; gap: 12px; background: #fafafa;
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
    padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 14px; width: 100%; transition: 0.2s;
}
.input:focus { border-color: #0f766e; outline: none; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
.textarea { height: 100px; resize: none; }

/* CHECKBOX DANH MỤC */
.category-list {
    height: 100px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;
    background: #fff; padding: 10px; border-color: #ddd;
}
.checkbox-label {
    display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: #333;
}
.checkbox-label input[type="checkbox"] {
    width: 16px; height: 16px; accent-color: #0f766e; cursor: pointer;
}

/* ẢNH GRID */
.header-label { margin-bottom: 12px; display: block; text-align: center; }
.img-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
.img-box {
    aspect-ratio: 1; background: #fff; border: 2px dashed #d1d5db; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; transition: 0.2s;
}
.img-box:hover { border-color: #0f766e; background: #f0fdfa; }
.img-box.filled { border-style: solid; border-color: #e5e7eb; cursor: default; } /* Bỏ cursor pointer khi có ảnh để dễ ấn nút X */
.placeholder { text-align: center; color: #aaa; }
.placeholder i { font-size: 24px; margin-bottom: 5px; display: block; }
.tiny-text { font-size: 12px; }
.img-box img { width: 100%; height: 100%; object-fit: cover; }
.btn-remove {
    position: absolute; top: 4px; right: 4px; background: rgba(220, 38, 38, 0.9); color: white; border: none;
    border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer;
    z-index: 10;
}
.badge-main { position: absolute; bottom: 0; width: 100%; background: #0f766e; color: white; font-size: 10px; text-align: center; padding: 2px 0; pointer-events: none; }

/* MODEL 3D */
.model-section { margin-top: 20px; }
.model-box {
    background: #fff; border: 2px dashed #ccc; border-radius: 8px; height: 70px;
    display: flex; align-items: center; justify-content: center; color: #666; cursor: pointer;
    position: relative; overflow: hidden; transition: 0.2s;
}
.model-box:hover { border-color: #0f766e; background: #f0fdfa; }
.model-box.has-model {
    height: 250px; /* Phóng to box khi có model để dễ xem */
    border-style: solid; border-color: #e5e7eb; background: #f1f1f1; cursor: default;
}
.model-placeholder { display: flex; gap: 10px; align-items: center; }
.model-placeholder i { font-size: 20px; margin: 0; }
.model-remove { top: 8px; right: 8px; width: 28px; height: 28px; font-size: 16px; }

/* BUTTONS */
.btn-submit {
    background: #0f766e; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 8px; transition: 0.2s;
}
.btn-submit:hover { background: #115e59; transform: translateY(-1px); }
.btn-cancel { background: #fff; border: 1px solid #ddd; color: #555; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #f3f4f6; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .modal-card { width: 100%; height: 100vh; max-height: none; border-radius: 0; }
    .modal-grid { grid-template-columns: 1fr; } 
    .img-grid { grid-template-columns: repeat(2, 1fr); } 
}
</style>