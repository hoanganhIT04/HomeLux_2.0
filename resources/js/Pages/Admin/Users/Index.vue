<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { debounce } from 'lodash'

/* ================= PROPS ================= */
const props = defineProps({
  users: Object,
  filters: Object,
  stats: Object
})

/* ================= STATS ================= */
const stats = computed(() => [
  { label: 'Tổng người dùng', value: props.stats.total || 0, icon: 'fa-users' },
  { label: 'Khách hàng', value: props.stats.user || 0, icon: 'fa-user' },
  { label: 'Quản trị viên', value: props.stats.admin || 0, icon: 'fa-user-tie' },
])

/* ================= SEARCH ================= */
const search = ref(props.filters?.search || '')

watch(search, debounce((value) => {
  router.get(route('admin.users.index'), { search: value }, {
    preserveState: true,
    replace: true,
    preserveScroll: true
  })
}, 500))

/* ================= ACTIONS ================= */
const deleteUser = (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa người dùng này không? Hành động này không thể hoàn tác!')) {
    router.delete(route('admin.users.destroy', id), {
      preserveScroll: true,
      onError: (errors) => {
        if (errors.message) alert(errors.message);
      }
    });
  }
}

/* ================= FORMAT ================= */
const getRoleLabel = (role) => {
  if (role === 'user') return 'Khách hàng'
  if (role === 'admin') return 'Quản trị viên'
  return role
}

const getRoleClass = (role) => {
  if (role === 'user') return 'customer' // Dùng class "customer"
  if (role === 'admin') return 'manager' // Dùng class "manager" (vàng)
  return 'staff'
}
</script>

<template>
  <AdminLayout title="Quản Lý Người Dùng">
    <div class="dashboard">

      <!-- ================= STATS ================= -->
      <div class="stats-wrapper">
        <div v-for="item in stats" :key="item.label" class="stat-card">
          <i :class="['fa-solid', item.icon]"></i>
          <div>
            <p class="stat-label">{{ item.label }}</p>
            <p class="stat-number">{{ item.value }}</p>
          </div>
        </div>
      </div>

      <!-- ================= TABLE CARD ================= -->
      <div class="table-card">
        <div class="table-header">
          <h3 class="section__title"><i class="fa-solid fa-users"></i> Danh sách người dùng</h3>
          <div class="header-actions">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="search" type="text" class="form__input" placeholder="Tìm tên hoặc email..." />
            </div>
          </div>
        </div>

        <!-- Báo lỗi nếu có -->
        <div v-if="$page.props.errors.message" class="alert-error" style="margin-bottom: 20px; color: red;">
          {{ $page.props.errors.message }}
        </div>

        <div v-if="$page.props.flash && $page.props.flash.error" class="alert-error"
          style="padding: 10px; background: #fee2e2; color: #dc2626; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
          {{ $page.props.flash.error }}
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="u in users.data" :key="u.id">
                <td>#{{ u.id }}</td>
                <td class="name-cell">{{ u.name }}</td>
                <td>{{ u.email }}</td>
                <td>
                  <span :class="['status', getRoleClass(u.role)]">
                    {{ getRoleLabel(u.role) }}
                  </span>
                </td>
                <td>{{ new Date(u.created_at).toLocaleDateString('vi-VN') }}</td>
                <td class="table__action-cell">
                  <div class="table__actions">
                    <!-- Chỉ giữ lại nút xóa -->
                    <button @click="deleteUser(u.id)" class="action-btn action-btn--delete" aria-label="Xóa">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data || users.data.length === 0">
                <td colspan="6" style="padding: 20px; color: #6b7280;">Không tìm thấy người dùng nào.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" style="display: flex; justify-content: flex-end; margin-top: 20px;">
          <ul class="pagination">
            <li v-for="link in users.links" :key="link.label"
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
  </AdminLayout>
</template>

<style scoped>
/* CHỈ GIỮ LẠI NHỮNG CSS CƠ BẢN HOẶC ĐẶC THÙ, PHẦN CÒN LẠI DÙNG CỦA APP.CSS */
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

/* ===== BẢNG & LAYOUT ===== */
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
  width: 100%;
  padding: 9px 16px 9px 40px;
  border-radius: 999px;
  border: 1px solid #e5e7eb;
  transition: 0.2s;
  outline: none;
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

/* Badge màu */
.status {
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.customer {
  background: #e0f2fe;
  color: #0284c7;
}

.manager {
  background: #fef3c7;
  color: #d97706;
}

/* Kế thừa từ class app.css .action-btn nhưng tự tuỳ biến nút xóa */
.action-btn--delete:hover {
  background: #fee2e2;
  color: #dc2626;
}

/* Responsive Table như các trang khác */
@media (max-width: 768px) {
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

  tbody td::before { content: attr(data-label); font-weight: 600; color: #6b7280; text-align: left; padding-right: 15px; }

  tbody td:nth-child(1)::before { content: "ID"; }
  tbody td:nth-child(2)::before { content: "Tên"; }
  tbody td:nth-child(3)::before { content: "Email"; }
  tbody td:nth-child(4)::before { content: "Vai trò"; }
  tbody td:nth-child(5)::before { content: "Ngày tạo"; }

  /* Hành động */
  tbody td:nth-child(6) { justify-content: flex-end; padding-top: 1rem; margin-top: 0.5rem; }
  tbody td:nth-child(6)::before { display: none; }
  
  .table__actions { display: flex; gap: 6px; }
}
</style>