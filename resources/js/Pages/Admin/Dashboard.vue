<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { onMounted } from 'vue'
import Chart from 'chart.js/auto'

// Nhận dữ liệu thực tế từ Controller
const props = defineProps({
  totalRevenue: Number,
  newOrdersCount: Number,   // Đơn hàng mới
  lowStockCount: Number,    // Sắp hết hàng
  totalUsersCount: Number,  // Tổng người dùng
  chartLabels: Array,
  chartData: Array
})

// Hàm format tiền VNĐ
const fmtVND = (v) => new Intl.NumberFormat('vi-VN').format(v) + ' đ'

/* ================= CHART ================= */
onMounted(() => {
  const ctx = document.getElementById('revenueChart')
  if (!ctx) return

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.chartLabels,
      datasets: [{
        label: 'Doanh thu',
        data: props.chartData,
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15,118,110,0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 2 // Thu nhỏ chấm cho biểu đồ 30 ngày thoáng hơn
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: v => fmtVND(v)
          }
        }
      }
    }
  })
})
</script>

<template>
  <AdminLayout title="Tổng Quan Hệ Thống">

    <div class="dashboard">

      <div class="stats-wrapper">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-solid fa-coins"></i>
          </div>
          <div>
            <p class="stat-label">Tổng doanh thu</p>
            <p class="stat-number">{{ fmtVND(props.totalRevenue || 0) }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-solid fa-cart-shopping"></i>
          </div>
          <div>
            <p class="stat-label">Đơn hàng mới</p>
            <p class="stat-number">{{ props.newOrdersCount || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-solid fa-box-open"></i>
          </div>
          <div>
            <p class="stat-label">Sắp hết hàng</p>
            <p class="stat-number">{{ props.lowStockCount || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-solid fa-users"></i>
          </div>
          <div>
            <p class="stat-label">Tổng người dùng</p>
            <p class="stat-number">{{ props.totalUsersCount || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="table-card">
        <h3 class="section-title">
          <i class="fa-solid fa-chart-line"></i>
          Biểu đồ doanh thu 30 ngày gần nhất
        </h3>
        <div class="chart-wrapper">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>

    </div>

  </AdminLayout>
</template>

<style scoped>
/* ================= LAYOUT ================= */
.dashboard { display: flex; flex-direction: column; gap: 2rem; width: 100%; }

/* ================= STATS ================= */
.stats-wrapper { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; width: 100%; }
.stat-card { display: flex; align-items: center; gap: 16px; padding: 1.5rem; background: #ffffff; border-radius: 18px; border: 1px solid #e5e7eb; transition: 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); }
.stat-icon i { font-size: 2.2rem; color: #0f766e; }
.stat-card:hover { transform: translateY(-6px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06); }
.stat-label { font-size: 0.95rem; font-weight: 500; color: #6b7280; margin-bottom: 5px; }
.stat-number { font-size: 1.8rem; font-weight: 700; color: #111827; margin: 0; }

/* ================= CARD & CHART ================= */
.table-card { background: #ffffff; border-radius: 22px; padding: 2rem; border: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03); width: 100%; }
.section-title { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; font-size: 1.3rem; font-weight: 600; color: #111827; margin-top: 0; }
.section-title i { color: #0f766e; }
.chart-wrapper { height: 420px; width: 100%; position: relative; }

/* ================= RESPONSIVE ================= */
@media (max-width: 1024px) {
  .stats-wrapper { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .stats-wrapper { grid-template-columns: 1fr; }
  .table-card { padding: 1.2rem; }
  .chart-wrapper { height: 280px; }
  .section-title { font-size: 1.1rem; }
  .stat-number { font-size: 1.5rem; }
}
</style>