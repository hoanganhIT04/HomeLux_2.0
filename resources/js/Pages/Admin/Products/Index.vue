<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

/* ================= STATS ================= */
const stats = ref([
  { label: 'Sản phẩm phổ biến', value: 12, icon: 'fa-fire' },
  { label: 'Sản phẩm bán chạy', value: 8, icon: 'fa-chart-line' },
  { label: 'Sản phẩm sắp hết', value: 3, icon: 'fa-box-open' },
])

/* ================= PROPS ================= */
const props = defineProps({
  products: Object,
  filters: Object,
})

/* ================= SEARCH ================= */
const search = ref(props.filters.search || '')

// Tìm kiếm tự động (Tuỳ chọn: Nếu bạn đã setup Lodash thì dùng debounce, nếu chưa thì gõ phím Enter)
const handleSearch = () => {
  router.get(route('admin.products.index'), { search: search.value }, {
    preserveState: true,
    replace: true
  })
}
</script>

<template>
  <AdminLayout title="Quản Lý Sản Phẩm">
    <div class="dashboard">

      <div class="stats-wrapper">
        <div v-for="item in stats" :key="item.label" class="stat-card">
          <i :class="['fa-solid', item.icon]"></i>
          <div>
            <p class="stat-label">{{ item.label }}</p>
            <p class="stat-number">{{ item.value }}</p>
          </div>
        </div>
      </div>

      <div class="table-card">

        <div class="table-header">
          <h3 class="section__title"><i class="fa-solid fa-box"></i> Danh sách sản phẩm</h3>

          <div class="header-actions">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input 
                v-model="search" 
                @keyup.enter="handleSearch"
                type="text" 
                class="form__input"
                placeholder="Tìm kiếm sản phẩm..." 
              />
            </div>

            <button class="btn">
              <i class="fa-solid fa-plus"></i> Thêm sản phẩm
            </button>
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
                <td :class="{ low: p.stock <= 5 }">{{ p.stock }}</td>
                <td>{{ p.sold }}</td>

                <td class="table__action-cell">
                  <div class="table__actions">
                    <button class="action-btn action-btn--edit" aria-label="Sửa">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="action-btn action-btn--delete" aria-label="Xóa">
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
            <li v-for="link in products.links" :key="link.label" :class="[
              'pagination__item',
              {
                active: link.active,
                disabled: !link.url
              }
            ]">
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
  </AdminLayout>
</template>

<style scoped>
/* CHỈ GIỮ LẠI NHỮNG CSS ĐẶC THÙ RIÊNG CỦA TRANG ADMIN NÀY */

.dashboard {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* ===== STATS CARD (Giữ lại vì app.css chưa có khối này) ===== */
.stats-wrapper {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
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

/* ===== BẢNG & LAYOUT BẢNG ===== */
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

/* Setup lại padding input do dùng class .form__input chung */
.search-box input {
  padding-left: 40px !important;
  border-radius: 999px !important;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
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

/* Kế thừa .action-btn nhưng bổ sung màu Sửa/Xóa cho Admin */
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
  .product-img { margin: 0; }
  .table-card { padding: 1.2rem; }
  .table-header, .header-actions { flex-direction: column; align-items: stretch; width: 100%; }
  .search-box { width: 100%; }
  
  table, thead, tbody, th, td, tr { display: block; width: 100%; }
  thead { display: none; }
  
  tbody tr {
    background: #ffffff;
    padding: 1.2rem;
    border-radius: 18px;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
  }
  
  tbody td {
    position: relative;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0.7rem 1rem 0.7rem 110px;
    border-bottom: 1px solid #f1f5f9;
    text-align: right;
  }
  
  tbody td:last-child { border-bottom: none; }
  tbody td::before {
    position: absolute;
    left: 1rem;
    font-weight: 600;
    color: #6b7280;
  }
  
  tbody td:nth-child(1)::before { content: "ID"; }
  tbody td:nth-child(2)::before { content: "Hình ảnh"; }
  tbody td:nth-child(3)::before { content: "Tên"; }
  tbody td:nth-child(4)::before { content: "Giá"; }
  tbody td:nth-child(5)::before { content: "Tồn kho"; }
  tbody td:nth-child(6)::before { content: "Đã bán"; }
  tbody td:nth-child(7)::before { content: "Hành động"; }
}
</style>