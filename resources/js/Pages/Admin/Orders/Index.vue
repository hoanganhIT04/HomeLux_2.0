<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import OrderDetailModal from './OrderDetailModal.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'

const props = defineProps({
  orders: Object,
  filters: Object,
  stats: Object
})

const search = ref(props.filters?.search || '')
const showModal = ref(false)
const selectedOrder = ref(null)

watch(search, debounce((value) => {
  router.get(route('admin.orders.index'), { search: value }, {
    preserveState: true,
    replace: true,
    preserveScroll: true
  })
}, 500))

const openDetailModal = (order) => {
  selectedOrder.value = order
  showModal.value = true
}

import axios from 'axios'

const getStatusLabel = (status) => {
  const labels = {
    'pending': 'Chờ duyệt',
    'paid': 'Đã trả (MoMo)',
    'delivering': 'Đang giao',
    'completed': 'Hoàn thành',
    'cancelled': 'Đã hủy'
  }
  return labels[status]
}

/* ================= TOOLTIP HOVER DANH SÁCH ĐƠN HÀNG ================= */
const statusOrders = ref({})
const showTooltip = ref({})
const loadingStatus = ref({})

const fetchOrdersByStatus = async (statusGroup) => {
  if (!statusOrders.value[statusGroup]) {
    loadingStatus.value[statusGroup] = true
    try {
      const response = await axios.get(route('admin.orders.by_status', statusGroup))
      statusOrders.value[statusGroup] = response.data
    } catch (error) {
      console.error('Lỗi lấy đơn hàng:', error)
    } finally {
      loadingStatus.value[statusGroup] = false
    }
  }
}

let tooltipTimeout = null;

const handleMouseEnterStatus = (statusGroup) => {
  if (tooltipTimeout) clearTimeout(tooltipTimeout);
  Object.keys(showTooltip.value).forEach(k => showTooltip.value[k] = false);
  showTooltip.value[statusGroup] = true;
  fetchOrdersByStatus(statusGroup);
}

const handleMouseLeaveStatus = (statusGroup) => {
  tooltipTimeout = setTimeout(() => {
    showTooltip.value[statusGroup] = false;
  }, 300); // Debounce to allow moving mouse into the dropdown
}
</script>

<template>
  <AdminLayout title="Quản Lý Đơn Hàng">
    <div class="dashboard">
      <div class="stats-wrapper">
        <!-- Chờ duyệt -->
        <div class="stat-card" @mouseenter="handleMouseEnterStatus('pending')"
          @mouseleave="handleMouseLeaveStatus('pending')">
          <i class="fa-solid fa-clock"></i>
          <div>
            <p class="stat-label">Chờ duyệt</p>
            <p class="stat-number">{{ stats.pending }}</p>
          </div>
          <!-- Dropdown -->
          <div v-if="showTooltip['pending']" class="status-dropdown">
            <div v-if="loadingStatus['pending']" class="loading-text">Đang tải...</div>
            <div v-else-if="!statusOrders['pending'] || statusOrders['pending'].length === 0" class="empty-text">Không
              có đơn hàng</div>
            <ul v-else class="status-list">
              <li v-for="order in statusOrders['pending']" :key="order.id" @click.stop="openDetailModal(order)">
                <span class="order-id">#{{ order.id }}</span>
                <span class="order-name" :title="order.receiver_name">{{ order.receiver_name }}</span>
                <span class="order-price">{{ Number(order.total_price).toLocaleString() }}đ</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Đang giao -->
        <div class="stat-card" @mouseenter="handleMouseEnterStatus('delivering')"
          @mouseleave="handleMouseLeaveStatus('delivering')">
          <i class="fa-solid fa-truck"></i>
          <div>
            <p class="stat-label">Đang giao</p>
            <p class="stat-number">{{ stats.delivering }}</p>
          </div>
          <!-- Dropdown -->
          <div v-if="showTooltip['delivering']" class="status-dropdown">
            <div v-if="loadingStatus['delivering']" class="loading-text">Đang tải...</div>
            <div v-else-if="!statusOrders['delivering'] || statusOrders['delivering'].length === 0" class="empty-text">
              Không có đơn hàng</div>
            <ul v-else class="status-list">
              <li v-for="order in statusOrders['delivering']" :key="order.id" @click.stop="openDetailModal(order)">
                <span class="order-id">#{{ order.id }}</span>
                <span class="order-name" :title="order.receiver_name">{{ order.receiver_name }}</span>
                <span class="order-price">{{ Number(order.total_price).toLocaleString() }}đ</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Hoàn thành -->
        <div class="stat-card" @mouseenter="handleMouseEnterStatus('completed')"
          @mouseleave="handleMouseLeaveStatus('completed')">
          <i class="fa-solid fa-circle-check"></i>
          <div>
            <p class="stat-label">Đã hoàn thành</p>
            <p class="stat-number">{{ stats.completed }}</p>
          </div>
          <!-- Dropdown -->
          <div v-if="showTooltip['completed']" class="status-dropdown">
            <div v-if="loadingStatus['completed']" class="loading-text">Đang tải...</div>
            <div v-else-if="!statusOrders['completed'] || statusOrders['completed'].length === 0" class="empty-text">
              Không có đơn hàng</div>
            <ul v-else class="status-list">
              <li v-for="order in statusOrders['completed']" :key="order.id" @click.stop="openDetailModal(order)">
                <span class="order-id">#{{ order.id }}</span>
                <span class="order-name" :title="order.receiver_name">{{ order.receiver_name }}</span>
                <span class="order-price">{{ Number(order.total_price).toLocaleString() }}đ</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h3 class="section__title"><i class="fa-solid fa-cart-shopping"></i> Danh sách đơn hàng</h3>
          <div class="header-actions">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="search" type="text" class="form__input" placeholder="Tìm ID đơn hàng hoặc khách..." />
            </div>
          </div>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Ngày đặt</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="o in orders.data" :key="o.id">
                <td>#{{ o.id }}</td>
                <td class="name-cell">{{ o.receiver_name }}</td>
                <td>{{ Number(o.total_price).toLocaleString() }}đ</td>
                <td>{{ new Date(o.created_at).toLocaleDateString('vi-VN') }}</td>
                <td>
                  <span :class="['status', o.status]">
                    {{ getStatusLabel(o.status) }}
                  </span>
                </td>
                <td class="table__action-cell">
                  <div class="table__actions">
                    <button @click="openDetailModal(o)" class="action-btn action-btn--view" aria-label="Xem chi tiết">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="orders.last_page > 1" style="display: flex; justify-content: flex-end;">
          <ul class="pagination">
            <li v-for="link in orders.links" :key="link.label"
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

    <OrderDetailModal :show="showModal" :order="selectedOrder" @close="showModal = false" />
  </AdminLayout>
</template>
<style scoped>
/* CSS đồng bộ với Product và app.css */
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

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

.status {
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.pending {
  background: #fef3c7;
  color: #d97706;
}

.paid {
  background: #dcfce7;
  color: #16a34a;
}

.delivering {
  background: #dbeafe;
  color: #2563eb;
}

.completed {
  background: #dcfce7;
  color: #16a34a;
}

.cancelled {
  background: #fee2e2;
  color: #dc2626;
}

.dashboard {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* ================= STATS ================= */

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
  /* Thêm position relative cho thẻ cha dropdown */
  cursor: pointer;
  /* Cho phép click */
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
  width: 320px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 15px;
  z-index: 100;
  cursor: default;
  max-height: 300px;
  overflow-y: auto;
}

/* Mũi tên chỉ lên của Tooltip */
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

.status-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.status-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  padding: 10px 8px;
  border-bottom: 1px dashed #f1f5f9;
  cursor: pointer;
  border-radius: 6px;
  transition: 0.2s ease;
}

.status-list li:hover {
  background: #f0fdfa;
}

.status-list li:last-child {
  border-bottom: none;
}

.order-id {
  font-weight: 700;
  color: #0f766e;
  width: 50px;
}

.order-name {
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  margin: 0 10px;
}

.order-price {
  font-weight: 600;
  color: #111827;
}

/* ================= TABLE CARD ================= */

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

/* ================= SEARCH ================= */

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
}

.search-box input:focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

/* ================= TABLE ================= */

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

/* ================= STATUS BADGE ================= */

.status {
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.pending {
  background: #fef3c7;
  color: #d97706;
}

.shipping {
  background: #dbeafe;
  color: #2563eb;
}

.completed {
  background: #dcfce7;
  color: #16a34a;
}

/* ================= ACTION ================= */

.action-cell {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.btn-edit,
.btn-delete {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-edit {
  background: #e0f2fe;
  color: #0284c7;
}

.btn-delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn-edit:hover,
.btn-delete:hover {
  transform: scale(1.1);
}

/* ================= MOBILE ================= */

@media (max-width: 768px) {

  .action-cell {
    display: flex;
    justify-content: flex-end;
    /* đẩy hẳn sang phải */
    width: 100%;
  }

  .table-card {
    padding: 1.2rem;
  }

  .table-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
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

  tbody td:last-child {
    border-bottom: none;
  }

  tbody td::before {
    position: absolute;
    left: 1rem;
    font-weight: 600;
    color: #6b7280;
  }

  tbody td:nth-child(1)::before {
    content: "ID";
  }

  tbody td:nth-child(2)::before {
    content: "Khách hàng";
  }

  tbody td:nth-child(3)::before {
    content: "Tổng tiền";
  }

  tbody td:nth-child(4)::before {
    content: "Ngày đặt";
  }

  tbody td:nth-child(5)::before {
    content: "Trạng thái";
  }

  tbody td:nth-child(6)::before {
    content: "Hành động";
  }

}
</style>
