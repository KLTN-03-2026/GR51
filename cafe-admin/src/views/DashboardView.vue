<template>
  <div class="dashboard fade-in">
    <!-- Loading -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 4" :key="i" class="skeleton stat-skeleton"></div>
    </div>

    <template v-else>
      <!-- Stat Cards -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon revenue-icon">💰</div>
          <div class="stat-info">
            <span class="stat-label">Doanh thu hôm nay</span>
            <span class="stat-value">{{ formatMoney(data.thong_ke_hom_nay?.tong_doanh_thu) }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon order-icon">📋</div>
          <div class="stat-info">
            <span class="stat-label">Tổng đơn hôm nay</span>
            <span class="stat-value">{{ data.thong_ke_hom_nay?.tong_don || 0 }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon avg-icon">📊</div>
          <div class="stat-info">
            <span class="stat-label">Trung bình / đơn</span>
            <span class="stat-value">{{ formatMoney(data.thong_ke_hom_nay?.trung_binh_don) }}</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" :class="{ 'warning-icon': totalWarning > 0 }">⚠️</div>
          <div class="stat-info">
            <span class="stat-label">Nguyên liệu cảnh báo</span>
            <span class="stat-value">{{ totalWarning }}</span>
          </div>
        </div>
      </div>

      <!-- Stat Tổng hợp -->
      <div class="overall-row">
        <div class="overall-item">
          <span class="overall-label">📈 Tổng doanh thu</span>
          <span class="overall-value">{{ formatMoney(data.thong_ke_tong_hop?.tong_doanh_thu) }}</span>
        </div>
        <div class="overall-item">
          <span class="overall-label">🧾 Tổng đơn hàng</span>
          <span class="overall-value">{{ data.thong_ke_tong_hop?.tong_don || 0 }}</span>
        </div>
        <div class="overall-item">
          <span class="overall-label">⭐ Đánh giá TB</span>
          <span class="overall-value">{{ data.danh_gia_trung_binh || '—' }}</span>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="charts-row">
        <div class="card chart-card">
          <h3>Doanh thu 7 ngày gần nhất</h3>
          <div class="chart-container">
            <canvas ref="revenueChartRef"></canvas>
          </div>
        </div>
        <div class="card chart-card chart-small">
          <h3>Đơn hàng theo trạng thái</h3>
          <div class="chart-container doughnut-container">
            <canvas ref="orderChartRef"></canvas>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Top Món bán chạy -->
        <div class="card">
          <h3>🔥 Top món bán chạy hôm nay</h3>
          <div v-if="data.top_mon_ban_chay?.length" class="top-list">
            <div v-for="(mon, idx) in data.top_mon_ban_chay" :key="mon.ma_mon" class="top-item">
              <span class="top-rank">#{{ idx + 1 }}</span>
              <div class="top-info">
                <span class="top-name">{{ mon.ten_mon }}</span>
                <span class="top-price">{{ formatMoney(mon.gia_ban) }}</span>
              </div>
              <span class="badge badge-info">{{ mon.tong_ban }} ly</span>
            </div>
          </div>
          <p v-else class="text-muted" style="padding:20px;text-align:center">Chưa có dữ liệu</p>
        </div>

        <!-- Đơn hàng gần đây -->
        <div class="card">
          <h3>🕐 Đơn hàng gần đây</h3>
          <div v-if="data.don_hang_gan_day?.length">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Bàn</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="dh in data.don_hang_gan_day" :key="dh.id">
                  <td style="color:var(--text-primary);font-weight:500">{{ dh.ma_don_hang.slice(-8) }}</td>
                  <td>{{ dh.ten_ban }}</td>
                  <td style="color:var(--accent)">{{ formatMoney(dh.tong_tien) }}</td>
                  <td><span :class="statusClass(dh.trang_thai_don)" class="badge">{{ statusLabel(dh.trang_thai_don) }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-muted" style="padding:20px;text-align:center">Chưa có đơn hàng</p>
        </div>
      </div>

      <!-- Cảnh báo tồn kho -->
      <div v-if="data.canh_bao_ton_kho?.length" class="card warning-card">
        <h3>🚨 Cảnh báo tồn kho</h3>
        <div class="warning-grid">
          <div v-for="nl in data.canh_bao_ton_kho" :key="nl.ma_nguyen_lieu" class="warning-item">
            <span class="warning-name">{{ nl.ten_nguyen_lieu }}</span>
            <span :class="nl.trang_thai_kho === 'het_hang' ? 'badge badge-error' : 'badge badge-warning'">
              {{ nl.ton_kho }} {{ nl.don_vi_tinh }}
            </span>
          </div>
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
const revenueChartRef = ref(null)
const orderChartRef = ref(null)

const totalWarning = computed(() =>
  (data.value.thong_ke_hom_nay?.nguyen_lieu_sap_het || 0) +
  (data.value.thong_ke_hom_nay?.nguyen_lieu_het_hang || 0)
)

function formatMoney(v) {
  if (!v && v !== 0) return '0đ'
  return Number(v).toLocaleString('vi-VN') + 'đ'
}

function shortId(id) {
  if (!id) return ''
  return id.length > 12 ? '...' + id.slice(-8) : id
}

function statusLabel(s) {
  const m = { 0: 'Chờ xác nhận', 1: 'Đang pha', 2: 'Hoàn thành', 3: 'Đã hủy' }
  return m[s] || s
}

function statusClass(s) {
  const m = { 0: 'badge-error', 1: 'badge-warning', 2: 'badge-success', 3: 'badge-secondary' }
  return m[s] || ''
}

function buildRevenueChart() {
  if (!revenueChartRef.value || !data.value.doanh_thu_7_ngay) return
  new Chart(revenueChartRef.value, {
    type: 'line',
    data: {
      labels: data.value.doanh_thu_7_ngay.map(d => d.ngay),
      datasets: [{
        label: 'Doanh thu',
        data: data.value.doanh_thu_7_ngay.map(d => d.doanh_thu),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.1)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#f59e0b',
        pointRadius: 5,
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

function buildOrderChart() {
  if (!orderChartRef.value || !data.value.don_theo_trang_thai) return
  const d = data.value.don_theo_trang_thai
  new Chart(orderChartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['Chờ xác nhận', 'Đang pha', 'Hoàn thành', 'Đã hủy'],
      datasets: [{
        data: [d[0] || 0, d[1] || 0, d[2] || 0, d[3] || 0],
        backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#94a3b8'],
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

onMounted(async () => {
  try {
    const res = await api.getDashboard()
    data.value = res.data.data
  } catch (e) {
    console.error('Dashboard error:', e)
  } finally {
    loading.value = false
    await nextTick()
    await nextTick()
    buildRevenueChart()
    buildOrderChart()
  }
})
</script>

<style scoped>
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px; display: flex; align-items: center; gap: 16px; transition: all var(--transition-normal); }
.stat-card:hover { border-color: var(--accent); box-shadow: var(--shadow-glow); transform: translateY(-2px); }
.stat-icon { font-size: 2rem; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); background: var(--bg-tertiary); flex-shrink: 0; }
.warning-icon { background: var(--warning-bg) !important; }
.stat-info { display: flex; flex-direction: column; gap: 4px; }
.stat-label { font-size: 0.8rem; color: var(--text-muted); }
.stat-value { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }

.overall-row { display: flex; gap: 20px; margin-bottom: 24px; }
.overall-item { flex: 1; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
.overall-label { font-size: 0.85rem; color: var(--text-secondary); }
.overall-value { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--accent); }

.charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
.chart-card h3 { margin-bottom: 16px; font-size: 1rem; }
.chart-container { height: 260px; position: relative; }
.doughnut-container { height: 240px; }

.bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.bottom-row .card h3 { margin-bottom: 16px; font-size: 1rem; }

.top-list { display: flex; flex-direction: column; gap: 8px; }
.top-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-md); background: var(--bg-secondary); }
.top-rank { font-family: 'Outfit'; font-weight: 700; font-size: 1.1rem; color: var(--accent); width: 30px; }
.top-info { flex: 1; display: flex; flex-direction: column; }
.top-name { font-weight: 500; color: var(--text-primary); font-size: 0.9rem; }
.top-price { font-size: 0.78rem; color: var(--text-muted); }

.warning-card { margin-bottom: 24px; border-color: var(--warning) !important; }
.warning-card h3 { margin-bottom: 16px; font-size: 1rem; }
.warning-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.warning-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-secondary); border-radius: var(--radius-md); }
.warning-name { font-size: 0.88rem; color: var(--text-primary); }

.loading-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.stat-skeleton { height: 96px; border-radius: var(--radius-lg); }

@media (max-width: 1100px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-row, .bottom-row { grid-template-columns: 1fr; }
}
</style>
