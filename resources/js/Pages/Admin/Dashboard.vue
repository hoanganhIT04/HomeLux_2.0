<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { onMounted } from 'vue'
import Chart from 'chart.js/auto'

// Nhận dữ liệu thực tế từ Controller
const props = defineProps({
  totalRevenue: Number,
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
        pointRadius: 2
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
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  width: 100%;
}

/* ================= STATS ================= */
.stats-wrapper {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  justify-content: flex-start; /* Ép căn trái hoàn toàn */
}

.stat-card {
  flex: 0 1 380px; /* Rộng tối đa 380px, không bị dãn thô thiển */
  min-width: 280px;
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 1.8rem 2rem;
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #e5e7eb;
  transition: 0.3s ease;
  box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}

.stat-icon i {
  font-size: 2.5rem;
  color: #0f766e;
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.06);
}

.stat-label {
  font-size: 1rem;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 5px;
}

.stat-number {
  font-size: 2.2rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

/* ================= CARD & CHART ================= */
.table-card {
  background: #ffffff;
  border-radius: 22px;
  padding: 2rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 30px rgba(0,0,0,0.03);
  width: 100%; /* Ép biểu đồ tràn full viền */
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2rem;
  font-size: 1.3rem;
  font-weight: 600;
  color: #111827;
  margin-top: 0;
}

.section-title i {
  color: #0f766e;
}

.chart-wrapper {
  height: 420px; /* Tăng chiều cao để biểu đồ to, rõ ràng, không bị bé tẹo */
  width: 100%;
  position: relative;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {
  .table-card {
    padding: 1.2rem;
  }
  
  .stat-card {
    flex: 1 1 100%;
    max-width: 100%;
  }

  .chart-wrapper {
    height: 280px; /* Giảm chiều cao trên điện thoại cho vừa màn */
  }
}
</style>