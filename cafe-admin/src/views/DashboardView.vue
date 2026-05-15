<template>
  <div class="dashboard fade-in">
    <!-- Header with Live Indicator -->
    <div class="dashboard-header">
      <div class="header-left">
        <h2>Trang Tổng Quan</h2>
        <div class="live-indicator">
          <span class="dot"></span>
          Đang theo dõi trực tiếp
        </div>
      </div>
      <div class="header-right">
        <div class="server-time">
          📅 {{ currentDateTime }}
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 4" :key="i" class="skeleton stat-skeleton"></div>
    </div>

    <template v-else>
      <!-- Operational Stats Grid -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon revenue-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Doanh thu hôm nay</span>
            <span class="stat-value">{{ formatMoney(data.thong_ke_hom_nay?.tong_doanh_thu) }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon order-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Tổng đơn hôm nay</span>
            <span class="stat-value">{{ data.thong_ke_hom_nay?.tong_don || 0 }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon table-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Bàn đang dùng</span>
            <span class="stat-value">{{ data.thong_ke_ban?.ban_dang_dung }}/{{ data.thong_ke_ban?.tong_so_ban }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" :class="{ 'warning-icon': totalWarning > 0 }">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Nguyên liệu cần nhập</span>
            <span class="stat-value">{{ totalWarning }}</span>
          </div>
        </div>
      </div>

      <!-- Main Operational Row -->
      <div class="main-row">
        <!-- Live Chart: Hourly Revenue -->
        <div class="card chart-card">
          <div class="card-header">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
              Doanh thu theo giờ (Hôm nay)
            </h3>
            <span class="badge badge-secondary">Cập nhật 1 phút trước</span>
          </div>
          <div class="chart-container">
            <canvas ref="hourlyChartRef"></canvas>
          </div>
        </div>

        <!-- Table Status Progress -->
        <div class="card occupancy-card">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Công suất phục vụ
          </h3>
          <div class="occupancy-content">
            <div class="gauge-container">
              <div class="gauge-ring" :style="`--progress: ${occupancyPercent}%`"></div >
              <div class="gauge-value">
                <span class="percent">{{ occupancyPercent }}%</span>
                <span class="label">Đang sử dụng</span>
              </div>
            </div>
            <div class="occupancy-stats">
              <div class="occ-item">
                <span class="dot success"></span>
                <span>Bàn trống: <b>{{ data.thong_ke_ban?.ban_trong }}</b></span>
              </div>
              <div class="occ-item">
                <span class="dot warning"></span>
                <span>Đang dùng: <b>{{ data.thong_ke_ban?.ban_dang_dung }}</b></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Real-time Activity Feed -->
        <div class="card">
          <div class="card-header">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              Hoạt động gần đây
            </h3>
            <router-link to="/orders" class="view-all">Xem tất cả</router-link>
          </div>
          <div v-if="data.don_hang_gan_day?.length" class="activity-feed">
            <div v-for="dh in data.don_hang_gan_day" :key="dh.id" class="activity-item">
              <div class="activity-time">{{ dh.thoi_gian.split(' ')[1].slice(0, 5) }}</div>
              <div class="activity-dot" :class="statusClass(dh.trang_thai_don)"></div>
              <div class="activity-content">
                <div class="activity-title">
                  <b>{{ dh.ten_ban }}</b> - {{ dh.ma_don_hang.slice(-6) }}
                </div>
                <div class="activity-desc">
                  Giá trị: <span class="text-accent">{{ formatMoney(dh.tong_tien) }}</span> • 
                  {{ statusLabel(dh.trang_thai_don) }}
                </div>
              </div>
            </div>
          </div>
          <p v-else class="text-muted" style="padding:20px;text-align:center">Chưa có hoạt động</p>
        </div>

        <!-- Top Selling Today -->
        <div class="card">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:#EF4444"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
            Top món bán chạy hôm nay
          </h3>
          <div v-if="data.top_mon_ban_chay?.length" class="top-list">
            <div v-for="(mon, idx) in data.top_mon_ban_chay" :key="mon.ma_mon" class="top-item">
              <div class="top-rank">{{ idx + 1 }}</div>
              <div class="top-info">
                <span class="top-name">{{ mon.ten_mon }}</span>
                <div class="progress-bar-sm">
                  <div class="progress-fill" :style="`width: ${Math.min(100, (mon.tong_ban / data.top_mon_ban_chay[0].tong_ban) * 100)}%`"></div>
                </div>
              </div>
              <span class="badge badge-info">{{ mon.tong_ban }} ly</span>
            </div>
          </div>
          <p v-else class="text-muted" style="padding:20px;text-align:center">Chưa có dữ liệu</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import api from '@/services/api'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const loading = ref(true)
const data = ref({})
const hourlyChartRef = ref(null)
const currentDateTime = ref(new Date().toLocaleString('vi-VN'))
let refreshInterval = null
let chartInstance = null

const totalWarning = computed(() =>
  (data.value.thong_ke_hom_nay?.nguyen_lieu_sap_het || 0) +
  (data.value.thong_ke_hom_nay?.nguyen_lieu_het_hang || 0)
)

const occupancyPercent = computed(() => {
  if (!data.value.thong_ke_ban?.tong_so_ban) return 0
  return Math.round((data.value.thong_ke_ban.ban_dang_dung / data.value.thong_ke_ban.tong_so_ban) * 100)
})

function formatMoney(v) {
  if (!v && v !== 0) return '0đ'
  return Number(v).toLocaleString('vi-VN') + 'đ'
}

function statusLabel(s) {
  const m = { 0: 'Chờ xử lý', 1: 'Đang pha', 2: 'Hoàn thành', 3: 'Đã hủy' }
  return m[s] || 'Mới'
}

function statusClass(s) {
  const m = { 0: 'status-error', 1: 'status-warning', 2: 'status-success', 3: 'status-secondary' }
  return m[s] || ''
}

function buildHourlyChart() {
  if (!hourlyChartRef.value || !data.value.doanh_thu_theo_gio) return
  
  if (chartInstance) {
    chartInstance.destroy()
  }

  chartInstance = new Chart(hourlyChartRef.value, {
    type: 'bar',
    data: {
      labels: data.value.doanh_thu_theo_gio.map(d => d.gio),
      datasets: [{
        label: 'Doanh thu',
        data: data.value.doanh_thu_theo_gio.map(d => d.doanh_thu),
        backgroundColor: '#f59e0b',
        borderRadius: 4,
        hoverBackgroundColor: '#fbbf24',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1000 // Thêm animation cho mượt
      },
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
        y: { grid: { color: 'rgba(148,163,184,0.08)' }, ticks: { color: '#94a3b8', callback: v => (v/1000) + 'k' } }
      }
    }
  })
}

onMounted(async () => {
  await fetchData()

  setInterval(() => {
    currentDateTime.value = new Date().toLocaleString('vi-VN')
  }, 1000)

  // Tự động làm mới dữ liệu mỗi 30 giây để cập nhật trạng thái bàn/đơn hàng
  refreshInterval = setInterval(fetchData, 30000)
})

// Cleanup interval khi component bị destroy
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})

async function fetchData() {
  try {
    const res = await api.getDashboard()
    data.value = res.data.data
    
    await nextTick()
    // Re-build chart if data changed
    buildHourlyChart()
  } catch (e) {
    console.error('Dashboard error:', e)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 24px; }

.dashboard-header { display: flex; justify-content: space-between; align-items: center; }
.header-left h2 { font-size: 1.5rem; margin-bottom: 4px; }
.live-indicator { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--success); font-weight: 500; }
.live-indicator .dot { width: 8px; height: 8px; background: var(--success); border-radius: 50%; animation: pulse 2s infinite; }
.server-time { color: var(--text-muted); font-size: 0.9rem; background: var(--bg-secondary); padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border-color); }

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 16px; transition: all 0.3s ease; }
.stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-glow); border-color: var(--accent); }
.stat-icon { font-size: 1.8rem; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: var(--bg-secondary); }
.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 700; color: var(--text-primary); }

.main-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.chart-container { height: 300px; }

.occupancy-card { display: flex; flex-direction: column; }
.occupancy-content { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 24px; }
.gauge-container { position: relative; width: 160px; height: 160px; display: flex; align-items: center; justify-content: center; }
.gauge-ring { width: 100%; height: 100%; border-radius: 50%; background: conic-gradient(var(--accent) calc(var(--progress) * 1%), var(--bg-secondary) 0); mask: radial-gradient(transparent 65%, black 66%); -webkit-mask: radial-gradient(transparent 65%, black 66%); }
.gauge-value { position: absolute; display: flex; flex-direction: column; align-items: center; }
.gauge-value .percent { font-size: 2rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-primary); }
.gauge-value .label { font-size: 0.7rem; color: var(--text-muted); }
.occupancy-stats { display: flex; flex-direction: column; gap: 8px; width: 100%; }
.occ-item { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; padding: 10px; background: var(--bg-secondary); border-radius: 8px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot.success { background: var(--success); }
.dot.warning { background: var(--warning); }

.bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.activity-feed { display: flex; flex-direction: column; gap: 16px; position: relative; padding-left: 20px; }
.activity-feed::before { content: ''; position: absolute; left: 24px; top: 0; bottom: 0; width: 2px; background: var(--border-color); }
.activity-item { display: flex; gap: 20px; position: relative; }
.activity-time { font-size: 0.85rem; color: var(--accent); width: 50px; font-weight: 600; }
.activity-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--border-color); border: 2px solid var(--bg-card); z-index: 1; position: absolute; left: -1px; top: 4px; }
.activity-content { flex: 1; margin-top: -2px; }
.activity-title { font-size: 0.95rem; margin-bottom: 4px; }
.activity-desc { font-size: 0.85rem; color: var(--text-muted); }
.status-success { background: var(--success) !important; }
.status-warning { background: var(--warning) !important; }
.status-error { background: var(--error) !important; }

.top-list { display: flex; flex-direction: column; gap: 12px; }
.top-item { display: flex; align-items: center; gap: 12px; }
.top-rank { width: 24px; font-weight: 800; color: var(--text-muted); font-family: 'Outfit'; }
.top-info { flex: 1; }
.top-name { font-size: 0.9rem; font-weight: 500; margin-bottom: 4px; display: block; }
.progress-bar-sm { height: 6px; background: var(--bg-secondary); border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; background: var(--accent); border-radius: 3px; }

.view-all { font-size: 0.85rem; color: var(--accent); text-decoration: none; }
.view-all:hover { text-decoration: underline; }

@media (max-width: 1100px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .main-row, .bottom-row { grid-template-columns: 1fr; }
}
</style>
