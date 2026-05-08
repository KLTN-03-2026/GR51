<template>
  <div class="reports-view fade-in">
    <div class="toolbar">
      <h3>Thống kê & Báo cáo</h3>
      <div class="toolbar-actions">
        <select v-model="dateRange" class="filter-select" @change="fetchData">
          <option value="today">Hôm nay</option>
          <option value="7days">7 ngày qua</option>
          <option value="30days">30 ngày qua</option>
          <option value="this_month">Tháng này</option>
        </select>
        <button class="btn btn-secondary" @click="exportData">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Xuất báo cáo
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 4" :key="i" class="skeleton stat-skeleton"></div>
    </div>

    <template v-else>
      <!-- Summary Cards -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon revenue-icon">💰</div>
          <div class="stat-info">
            <span class="stat-label">Tổng doanh thu</span>
            <span class="stat-value">{{ formatMoney(revenueData?.summary?.tong_doanh_thu) }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon order-icon">📦</div>
          <div class="stat-info">
            <span class="stat-label">Tổng số đơn</span>
            <span class="stat-value">{{ revenueData?.summary?.tong_don || 0 }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon avg-icon">📈</div>
          <div class="stat-info">
            <span class="stat-label">Trung bình / đơn</span>
            <span class="stat-value">{{ formatMoney(revenueData?.summary?.trung_binh_don) }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cancel-icon" style="background:var(--warning-bg)">⚠️</div>
          <div class="stat-info">
            <span class="stat-label">Tỷ lệ huỷ đơn</span>
            <span class="stat-value">{{ revenueData?.summary?.ty_le_huy || 0 }}%</span>
          </div>
        </div>
      </div>

      <!-- Charts Row 1 -->
      <div class="charts-row">
        <div class="card chart-card">
          <h3>Doanh thu theo thời gian</h3>
          <div class="chart-container">
            <canvas ref="revenueChartRef"></canvas>
          </div>
        </div>
        <div class="card chart-card chart-small">
          <h3>Tỷ trọng Danh mục</h3>
          <div class="chart-container doughnut-container">
            <canvas ref="categoryChartRef"></canvas>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Top Món bán chạy -->
        <div class="card">
          <h3>🔥 Top 10 Món bán chạy nhất</h3>
          <div v-if="bestSellersData?.top_mons?.length">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tên món</th>
                  <th>Danh mục</th>
                  <th>Đã bán</th>
                  <th>Doanh thu mang lại</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(mon, idx) in bestSellersData.top_mons" :key="mon.id">
                  <td><span class="top-rank">{{ idx + 1 }}</span></td>
                  <td style="color:var(--text-primary);font-weight:500">{{ mon.ten_mon }}</td>
                  <td>{{ mon.danh_muc }}</td>
                  <td><span class="badge badge-info">{{ mon.tong_ban }}</span></td>
                  <td style="color:var(--accent)">{{ formatMoney(mon.tong_doanh_thu_mon) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-muted" style="padding:20px;text-align:center">Chưa có dữ liệu</p>
        </div>

        <!-- Order Overview -->
        <div class="card">
          <h3>Trạng thái & Thanh toán</h3>
          <div class="overview-grid" v-if="ordersData?.by_status">
            <div class="overview-box">
              <h4>Trạng thái đơn</h4>
              <ul>
                <li><span>Chờ xử lý:</span> <b>{{ ordersData.by_status.cho_xu_ly }}</b></li>
                <li><span>Đang pha:</span> <b>{{ ordersData.by_status.dang_pha }}</b></li>
                <li><span>Hoàn thành:</span> <b style="color:var(--success)">{{ ordersData.by_status.hoan_thanh }}</b></li>
                <li><span>Đã huỷ:</span> <b style="color:var(--error)">{{ ordersData.by_status.da_huy }}</b></li>
              </ul>
            </div>
            <div class="overview-box">
              <h4>Phương thức thanh toán</h4>
              <ul>
                <li><span>Tiền mặt:</span> <b>{{ ordersData.by_payment.tien_mat }}</b></li>
                <li><span>Chuyển khoản:</span> <b style="color:var(--primary)">{{ ordersData.by_payment.chuyen_khoan }}</b></li>
              </ul>
            </div>
            <div class="overview-box full-width" v-if="ordersData?.cancel_reasons?.length">
              <h4>Lý do huỷ biến phổ</h4>
              <ul>
                <li v-for="cr in ordersData.cancel_reasons" :key="cr.ly_do">
                  <span>{{ cr.ly_do }}:</span> <b style="color:var(--error)">{{ cr.so_luong }} đơn</b>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import api from '@/services/api'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const loading = ref(true)
const dateRange = ref('30days')

const revenueData = ref(null)
const bestSellersData = ref(null)
const ordersData = ref(null)

const revenueChartRef = ref(null)
const categoryChartRef = ref(null)
let revChartInstance = null
let catChartInstance = null

function formatMoney(v) {
  if (!v && v !== 0) return '0đ'
  return Number(v).toLocaleString('vi-VN') + 'đ'
}

function getDates() {
  const end = new Date()
  let start = new Date()
  
  if (dateRange.value === 'today') {
    // Start is today
  } else if (dateRange.value === '7days') {
    start.setDate(end.getDate() - 7)
  } else if (dateRange.value === '30days') {
    start.setDate(end.getDate() - 30)
  } else if (dateRange.value === 'this_month') {
    start = new Date(end.getFullYear(), end.getMonth(), 1)
  }

  const formatLocal = (d) => {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };

  return {
    start_date: formatLocal(start),
    end_date: formatLocal(end)
  }
}

async function fetchData() {
  loading.value = true
  try {
    const params = getDates()
    
    const [revRes, bestRes, ordRes] = await Promise.all([
      api.getReportRevenue(params),
      api.getReportBestSellers(params),
      api.getReportOrdersOverview(params)
    ])
    
    revenueData.value = revRes.data.data
    bestSellersData.value = bestRes.data.data
    ordersData.value = ordRes.data.data
  } catch (error) {
    console.error('Failed to fetch reports:', error)
  } finally {
    loading.value = false
    await nextTick()
    buildCharts()
  }
}

function buildCharts() {
  if (revChartInstance) revChartInstance.destroy()
  if (catChartInstance) catChartInstance.destroy()

  // Build Revenue Line Chart
  if (revenueChartRef.value && revenueData.value?.chart_data) {
    const chartData = revenueData.value.chart_data
    revChartInstance = new Chart(revenueChartRef.value, {
      type: 'line',
      data: {
        labels: chartData.map(d => d.date),
        datasets: [{
          label: 'Doanh thu',
          data: chartData.map(d => d.revenue),
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245,158,11,0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#f59e0b',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(148,163,184,0.08)' }, ticks: { color: '#94a3b8' } },
          y: { grid: { color: 'rgba(148,163,184,0.08)' }, ticks: { color: '#94a3b8', callback: v => (v/1000) + 'k' } }
        }
      }
    })
  }

  // Build Category Doughnut Chart
  if (categoryChartRef.value && bestSellersData.value?.danh_muc_chart) {
    const dMap = bestSellersData.value.danh_muc_chart
    const labels = Object.keys(dMap)
    const values = Object.values(dMap)
    
    catChartInstance = new Chart(categoryChartRef.value, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 16 } } }
      }
    })
  }
}

function exportData() {
  alert('Tính năng xuất báo cáo đang được hoàn thiện!')
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.toolbar h3 { font-size: 1.5rem; color: var(--text-primary); }
.toolbar-actions { display: flex; gap: 12px; align-items: center; }

.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px; display: flex; align-items: center; gap: 16px; transition: all var(--transition-normal); }
.stat-card:hover { border-color: var(--accent); box-shadow: var(--shadow-glow); transform: translateY(-2px); }
.stat-icon { font-size: 2rem; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); background: var(--bg-tertiary); flex-shrink: 0; }
.stat-info { display: flex; flex-direction: column; gap: 4px; }
.stat-label { font-size: 0.8rem; color: var(--text-muted); }
.stat-value { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }

.charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
.chart-card h3 { margin-bottom: 16px; font-size: 1rem; color: var(--text-primary); }
.chart-container { height: 300px; position: relative; }
.doughnut-container { height: 280px; }

.bottom-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
.bottom-row .card h3 { margin-bottom: 16px; font-size: 1rem; color: var(--text-primary); }

.top-rank { font-family: 'Outfit'; font-weight: 700; font-size: 1.1rem; color: var(--accent); }

.overview-grid { display: flex; flex-direction: column; gap: 16px; }
.overview-box { background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-md); }
.overview-box.full-width { grid-column: 1 / -1; }
.overview-box h4 { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
.overview-box ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
.overview-box li { display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--text-primary); }

.loading-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.stat-skeleton { height: 96px; border-radius: var(--radius-lg); }

@media (max-width: 1100px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-row, .bottom-row { grid-template-columns: 1fr; }
}
</style>
