<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CategoryModal from './CategoryModal.vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { debounce } from 'lodash';

import axios from 'axios'

/* ================= PROPS TỪ CONTROLLER ================= */
const props = defineProps({
  categories: Object,
  filters: Object,
  allCategories: Array,
  totalCategories: Number
})

const page = usePage()

/* ================= STATS ================= */
const stats = computed(() => [
  { label: 'Tổng danh mục', value: props.totalCategories || 0, icon: 'fa-layer-group', key: 'tree' },
])

/* ================= TOOLTIP TREE ================= */
const categoryTree = ref([])
const showTooltip = ref(false)
const loadingTree = ref(false)

const loadCategoryTree = async () => {
  if (categoryTree.value.length === 0) {
    loadingTree.value = true
    try {
      const response = await axios.get(route('admin.categories.tree'))
      categoryTree.value = response.data
    } catch (error) {
      console.error('Lỗi lấy cây danh mục:', error)
    } finally {
      loadingTree.value = false
    }
  }
}

let tooltipTimeout = null;

const handleMouseEnter = () => {
  if (tooltipTimeout) clearTimeout(tooltipTimeout);
  showTooltip.value = true;
  loadCategoryTree();
}

const handleMouseLeave = () => {
  tooltipTimeout = setTimeout(() => {
    showTooltip.value = false;
  }, 300);
}

/* ================= SEARCH ================= */
const search = ref(props.filters.search || '')

watch(search, debounce((value) => {
  router.get(route('admin.categories.index'), { search: value }, {
    preserveState: true,
    replace: true,
    preserveScroll: true
  })
}, 500))

/* ================= MODAL LOGIC ================= */
const showModal = ref(false)
const editMode = ref(false)
const selectedCategory = ref(null)

const openCreateModal = () => {
  editMode.value = false
  selectedCategory.value = null
  showModal.value = true
}

const openEditModal = (category) => {
  editMode.value = true
  selectedCategory.value = category
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedCategory.value = null
}

const deleteCategory = (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
    router.delete(route('admin.categories.destroy', id), {
      preserveScroll: true,
      onError: (errors) => {
        if (errors.message) alert(errors.message);
      }
    });
  }
}
</script>

<template>
  <AdminLayout title="Quản Lý Danh Mục">

    <div class="dashboard">

      <div class="stats-wrapper">
        <div v-for="item in stats" :key="item.label" class="stat-card"
          @mouseenter="item.key === 'tree' ? handleMouseEnter() : null"
          @mouseleave="item.key === 'tree' ? handleMouseLeave() : null">
          <i :class="['fa-solid', item.icon]"></i>
          <div>
            <p class="stat-label">{{ item.label }}</p>
            <p class="stat-number">{{ item.value }}</p>
          </div>

          <!-- Dropdown Cây Danh Mục -->
          <div v-if="item.key === 'tree' && showTooltip" class="status-dropdown">
            <div v-if="loadingTree" class="loading-text">Đang tải...</div>
            <div v-else-if="categoryTree.length === 0" class="empty-text">Chưa có danh mục nào</div>
            <ul v-else class="tree-list">
              <template v-for="parent in categoryTree" :key="parent.id">
                <!-- Cha -->
                <li class="tree-parent" @click.stop="openEditModal(parent)">
                  <span class="cat-name">{{ parent.name }}</span>
                </li>
                <!-- Con (Thụt lề) -->
                <li v-for="child in parent.children" :key="child.id" class="tree-child"
                  @click.stop="openEditModal(child)">
                  <i class="fa-solid fa-arrow-turn-up fa-rotate-90 child-icon"></i>
                  <span class="cat-name child-name">{{ child.name }}</span>
                </li>
              </template>
            </ul>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h3 class="section__title"><i class="fa-solid fa-layer-group"></i> Danh sách danh mục</h3>

          <div class="header-actions">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="search" type="text" class="form__input" placeholder="Tìm kiếm danh mục..." />
            </div>

            <button class="btn" @click="openCreateModal">
              <i class="fa-solid fa-plus"></i> Thêm danh mục
            </button>
          </div>
        </div>

        <div v-if="$page.props.errors.message" class="alert-error">
          {{ $page.props.errors.message }}
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tên danh mục</th>
                <th>Số sản phẩm</th>
                <th>Hành động</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="c in categories.data" :key="c.id">
                <td>#{{ c.id }}</td>
                <td><img :src="c.image_url" class="product-img" /></td>
                <td class="name-cell">{{ c.name }}</td>
                <td>{{ c.products_count }}</td>

                <td class="table__action-cell">
                  <div class="table__actions">
                    <button @click="openEditModal(c)" class="action-btn action-btn--edit" aria-label="Sửa">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button @click="deleteCategory(c.id)" class="action-btn action-btn--delete" aria-label="Xóa">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="categories.last_page > 1" style="display: flex; justify-content: flex-end;">
          <ul class="pagination">
            <li v-for="link in categories.links" :key="link.label"
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

    <CategoryModal :show="showModal" :editMode="editMode" :category="selectedCategory" :allCategories="allCategories"
      @close="closeModal" />

  </AdminLayout>
</template>

<style scoped>
/* CHỈ GIỮ LẠI NHỮNG CSS ĐẶC THÙ RIÊNG CỦA TRANG ADMIN NÀY (Giống hệt Products/Index.vue) */

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

/* ================= TOOLTIP DROPDOWN ================= */
.status-dropdown {
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
  max-height: 400px;
  overflow-y: auto;
}

.status-dropdown::before {
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

.tree-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
}

.tree-parent {
  padding: 10px 12px;
  font-weight: 600;
  color: #0f766e;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.2s ease;
  border-bottom: 1px solid #f1f5f9;
}

.tree-parent:hover {
  background: #f0fdfa;
}

.tree-child {
  padding: 8px 12px 8px 30px;
  /* Thụt vào */
  font-weight: 500;
  color: #4b5563;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.2s ease;
}

.tree-child:hover {
  background: #f8fafc;
  color: #0f766e;
}

.child-icon {
  font-size: 12px;
  color: #cbd5e1;
  margin-bottom: 3px;
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

/* Ghi đè padding input vì dùng class .form__input chung */
.search-box input {
  padding-left: 40px !important;
  border-radius: 999px !important;
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

/* Các thuộc tính riêng của Category */
.badge-parent {
  background-color: #e0f2fe;
  color: #0369a1;
  padding: 4px 10px;
  border-radius: 99px;
  font-size: 12px;
  font-weight: 600;
}

.text-muted {
  color: #9ca3af;
}

.alert-error {
  background-color: #fee2e2;
  color: #dc2626;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 15px;
  font-size: 14px;
  text-align: center;
  font-weight: 500;
}

/* Kế thừa .action-btn từ app.css nhưng bổ sung màu Sửa/Xóa cho Admin */
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
  .product-img {
    margin: 0;
    width: 50px;
    height: 50px;
  }

  .table-card {
    padding: 1.2rem;
  }

  .table-header,
  .header-actions {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }

  .search-box {
    width: 100%;
  }

  table,
  thead,
  tbody,
  th,
  td,
  tr {
    display: block;
    width: 100%;
  }

  thead {
    display: none;
  }

  tbody tr {
    background: #ffffff;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
  }

  tbody td {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px solid #f1f5f9;
    text-align: right;
    font-size: 0.9rem;
  }

  tbody td:last-child {
    border-bottom: none;
  }

  tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #6b7280;
    text-align: left;
    padding-right: 15px;
  }

  /* Đánh index cột theo bảng Categories (6 cột) */
  tbody td:nth-child(1)::before {
    content: "ID";
  }

  tbody td:nth-child(2)::before {
    content: "Hình ảnh";
  }

  tbody td:nth-child(3)::before {
    content: "Tên danh mục";
  }

  tbody td:nth-child(4)::before {
    content: "Danh mục cha";
  }

  tbody td:nth-child(5)::before {
    content: "Số sản phẩm";
  }

  /* Hành động */
  tbody td:nth-child(6) {
    justify-content: flex-end;
    padding-top: 1rem;
    margin-top: 0.5rem;
  }

  tbody td:nth-child(6)::before {
    display: none;
  }

  .table__actions {
    display: flex;
    gap: 6px;
  }
}
</style>