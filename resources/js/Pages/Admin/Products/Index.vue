<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ProductModal from './ProductModal.vue';
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash';
import axios from 'axios'; // Bổ sung axios để gọi API

/* ================= PROPS ================= */
const props = defineProps({
  products: Object,
  filters: Object,
  categories: Array,
  lowStockCount: Number,
  bestSellingCount: Number,
  popularCount: Number,
})

/* ================= STATS ================= */
const stats = ref([
  { label: 'Sản phẩm phổ biến', value: props.popularCount, icon: 'fa-fire' },
  { label: 'Sản phẩm bán chạy', value: props.bestSellingCount, icon: 'fa-chart-line' },
  { label: 'Sản phẩm sắp hết', value: props.lowStockCount, icon: 'fa-box-open' },
])

/* ================= TÍNH NĂNG: SẢN PHẨM SẮP HẾT (HOVER) ================= */
const lowStockProducts = ref([])
const showLowStockTooltip = ref(false)
const isLoadingLowStock = ref(false)

const handleMouseEnterLowStock = async () => {
  showLowStockTooltip.value = true;
  // Chỉ gọi API 1 lần nếu chưa có dữ liệu để tối ưu hiệu suất
  if (lowStockProducts.value.length === 0) {
    isLoadingLowStock.value = true;
    try {
      const response = await axios.get(route('admin.products.low_stock'));
      lowStockProducts.value = response.data;

      // Tự động cập nhật lại số lượng (value) trên thẻ card cho chính xác với DB
      const lowStockStat = stats.value.find(s => s.label === 'Sản phẩm sắp hết');
      if (lowStockStat) lowStockStat.value = response.data.length;

    } catch (error) {
      console.error('Lỗi khi lấy dữ liệu sản phẩm sắp hết', error);
    } finally {
      isLoadingLowStock.value = false;
    }
  }
}

const handleMouseLeaveLowStock = () => {
  showLowStockTooltip.value = false;
}

/* ================= SEARCH & LỌC ================= */
const search = ref(props.filters.search || '')
const filterCategory = ref(props.filters.category || '')
const sortSold = ref(props.filters.sort_sold || '')

watch([search, filterCategory, sortSold], debounce(([newSearch, newCat, newSort]) => {
  router.get(route('admin.products.index'), { search: newSearch, category: newCat, sort_sold: newSort }, {
    preserveState: true,
    replace: true,
    preserveScroll: true
  })
}, 500))

/* ================= QUẢN LÝ MODAL ================= */
const showModal = ref(false)
const editMode = ref(false)
const selectedProduct = ref(null)

const openCreateModal = () => {
  editMode.value = false
  selectedProduct.value = null
  showModal.value = true
}

const openEditModal = (product) => {
  editMode.value = true
  selectedProduct.value = product
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedProduct.value = null
}

const deleteProduct = (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này không? Tất cả hình ảnh và dữ liệu liên quan sẽ bị xóa vĩnh viễn!')) {
    router.delete(route('admin.products.destroy', id), {
      preserveScroll: true,
    });
  }
}
</script>

<template>
  <AdminLayout title="Quản Lý Sản Phẩm">
    <div class="dashboard">

      <div class="stats-wrapper">
        <div v-for="item in stats" :key="item.label" class="stat-card"
          @mouseenter="item.label === 'Sản phẩm sắp hết' ? handleMouseEnterLowStock() : null"
          @mouseleave="item.label === 'Sản phẩm sắp hết' ? handleMouseLeaveLowStock() : null">
          <i :class="['fa-solid', item.icon]"></i>
          <div>
            <p class="stat-label">{{ item.label }}</p>
            <p class="stat-number">{{ item.value }}</p>
          </div>

          <div v-if="item.label === 'Sản phẩm sắp hết' && showLowStockTooltip" class="low-stock-dropdown">
            <div v-if="isLoadingLowStock" class="loading-text">Đang tải dữ liệu...</div>
            <div v-else-if="lowStockProducts.length === 0" class="empty-text">Chưa có sản phẩm nào sắp hết</div>
            <ul v-else class="low-stock-list">
              <li v-for="prod in lowStockProducts" :key="prod.id">
                <span class="prod-name" :title="prod.name">{{ prod.name }}</span>
                <span class="prod-qty">Kho: <strong>{{ prod.quantity }}</strong></span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h3 class="section__title"><i class="fa-solid fa-box"></i> Danh sách sản phẩm</h3>

          <div class="header-actions">
            <!-- Filter Danh Mục -->
            <select v-model="filterCategory" class="filter-select">
              <option value="">Tất cả danh mục</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>

            <!-- Sắp xếp Số lượng bán -->
            <select v-model="sortSold" class="filter-select">
              <option value="">Sắp xếp mặc định</option>
              <option value="desc">Nhiều lượt bán nhất</option>
              <option value="asc">Ít lượt bán nhất</option>
            </select>

            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="search" type="text" class="form__input" placeholder="Tìm kiếm sản phẩm..." />
            </div>

            <button class="btn" @click="openCreateModal"> <i class="fa-solid fa-plus"></i> Thêm sản phẩm</button>
          </div>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Tồn kho</th>
                <th>Đã bán</th>
                <th>Hành động</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="p in products.data" :key="p.id">
                <td>#{{ p.id }}</td>
                <td><img :src="p.image" class="product-img" /></td>
                <td class="name-cell">{{ p.name }}</td>
                <td>{{ Number(p.price).toLocaleString() }}đ</td>
                <td :class="{ low: p.stock <= 10 }">{{ p.stock }}</td>
                <td>{{ p.sold }}</td>

                <td class="table__action-cell">
                  <div class="table__actions">
                    <button @click="openEditModal(p)" class="action-btn action-btn--edit" aria-label="Sửa">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button @click="deleteProduct(p.id)" class="action-btn action-btn--delete" aria-label="Xóa">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="products.last_page > 1" style="display: flex; justify-content: flex-end;">
          <ul class="pagination">
            <li v-for="link in products.links" :key="link.label"
              :class="['pagination__item', { active: link.active, disabled: !link.url }]">
              <Link v-if="link.url" :href="link.url" class="pagination__link" preserve-scroll>
                <span v-if="link.label.includes('Previous')">&laquo;</span>
                <span v-else-if="link.label.includes('Next')">&raquo;</span>
                <span v-else v-html="link.label"></span>
              </Link>
              <span v-else class="pagination__link">
                <span v-if="link.label.includes('Previous')">&laquo;</span>
                <span v-else-if="link.label.includes('Next')">&raquo;</span>
                <span v-else v-html="link.label"></span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <ProductModal :show="showModal" :editMode="editMode" :product="selectedProduct" :categories="categories"
      @close="closeModal" />
  </AdminLayout>
</template>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* ===== STATS CARD ===== */
.stats-wrapper {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  position: relative;
  z-index: 100;
}

.stat-card {
  flex: 1;
  min-width: 220px;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 1.5rem;
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #e5e7eb;
  transition: 0.3s ease;
  position: relative;
  /* Quan trọng để tooltip nằm đúng vị trí */
  cursor: pointer;
}

.stat-card i {
  font-size: 1.6rem;
  color: #0f766e;
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
}

.stat-label {
  font-size: 0.85rem;
  color: #6b7280;
}

.stat-number {
  font-size: 1.6rem;
  font-weight: 700;
  color: #111827;
}

/* ===== TOOLTIP SẢN PHẨM SẮP HẾT ===== */
.low-stock-dropdown {
  position: absolute;
  top: calc(100% + 15px);
  left: 50%;
  transform: translateX(-50%);
  width: 280px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 15px;
  z-index: 100;
  cursor: default;
  max-height: 250px;
  overflow-y: auto;
}

/* Mũi tên chỉ lên của Tooltip */
.low-stock-dropdown::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
  width: 12px;
  height: 12px;
  background: #fff;
  border-left: 1px solid #e5e7eb;
  border-top: 1px solid #e5e7eb;
}

.loading-text,
.empty-text {
  text-align: center;
  font-size: 0.9rem;
  color: #6b7280;
  padding: 10px 0;
}

.low-stock-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.low-stock-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  padding-bottom: 8px;
  border-bottom: 1px dashed #f1f5f9;
}

.low-stock-list li:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.prod-name {
  font-weight: 600;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 170px;
}

.prod-qty {
  color: #dc2626;
  /* Màu đỏ cảnh báo */
  font-size: 0.8rem;
}


/* ===== BẢNG & LAYOUT BẢNG (Giữ nguyên của bạn) ===== */
.table-card {
  background: #ffffff;
  border-radius: 22px;
  padding: 2rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-box {
  position: relative;
  width: 260px;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-box input {
  padding-left: 40px !important;
  border-radius: 999px !important;
  width: 100%;
  border: 1px solid #e5e7eb;
  padding: 8px 16px;
  outline: none;
}

.search-box input:focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

.filter-select {
  padding: 8px 16px;
  border-radius: 999px;
  border: 1px solid #e5e7eb;
  background: #fff;
  outline: none;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
  transition: 0.2s;
}

.filter-select:focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

.search-box input:focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: center;
  padding: 1rem 0;
  vertical-align: middle;
}

th {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}

tbody tr:hover {
  background: #f9fafb;
}

.product-img {
  width: 65px;
  height: 65px;
  object-fit: cover;
  border-radius: 14px;
  margin: auto;
}

.low {
  color: #dc2626;
  font-weight: 600;
}

.action-btn--edit:hover {
  background: #fef3c7;
  color: #d97706;
}

.action-btn--delete:hover {
  background: #fee2e2;
  color: #dc2626;
}

/* ===== BẢNG RESPONSIVE MOBILE ===== */
@media (max-width: 768px) {
  .product-img { margin: 0; width: 50px; height: 50px; }
  .table-card { padding: 1.2rem; }
  
  .table-header, .header-actions { flex-direction: column; align-items: stretch; width: 100%; }
  .search-box { width: 100%; }

  table, thead, tbody, th, td, tr { display: block; width: 100%; }
  thead { display: none; }

  tbody tr {
    background: #ffffff; padding: 1rem; border-radius: 12px;
    margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
  }

  tbody td {
    position: relative; display: flex; justify-content: space-between; align-items: center;
    padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; text-align: right; font-size: 0.9rem;
  }

  tbody td:last-child { border-bottom: none; }

  /* Đặt tiêu đề từ thuộc tính data-label */
  tbody td::before {
    content: attr(data-label); font-weight: 600; color: #6b7280; text-align: left; padding-right: 15px;
  }

  tbody td:nth-child(1)::before { content: "ID"; }
  tbody td:nth-child(2)::before { content: "Hình ảnh"; }
  tbody td:nth-child(3)::before { content: "Tên"; }
  tbody td:nth-child(4)::before { content: "Giá"; }
  tbody td:nth-child(5)::before { content: "Tồn kho"; }
  tbody td:nth-child(6)::before { content: "Đã bán"; }
  
  /* Căn riêng cho nút Hành động, đẩy xuống cuối cùng */
  tbody td:nth-child(7) { justify-content: flex-end; padding-top: 1rem; margin-top: 0.5rem; }
  tbody td:nth-child(7)::before { display: none; } /* Ẩn chữ Hành động trên mobile */
  
  .table__actions { display: flex; gap: 6px; }
}
</style>