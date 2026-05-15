<template>
  <div class="reports-view fade-in">
    <!-- Analysis Toolbar -->
    <div class="analysis-toolbar">
      <div class="toolbar-left">
        <h1>Phân Tích Kinh Doanh</h1>
        <p class="text-muted">Báo cáo chi tiết hiệu suất hoạt động của quán</p>
      </div>
      <div class="toolbar-right">
        <div class="date-range-picker">
          <div class="picker-group">
            <label>Khoảng thời gian</label>
            <select v-model="dateRange" class="filter-select" @change="fetchData">
              <option value="today">Hôm nay</option>
              <option value="7days">7 ngày qua</option>
              <option value="30days">30 ngày qua</option>
              <option value="this_month">Tháng này</option>
            </select>
          </div>
          <button class="btn btn-primary export-btn" @click="exportData">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Tải báo cáo
          </button>
        </div>
      </div>
    </div>

    <!-- Reports Navigation -->
    <div class="reports-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id" 
        class="tab-btn" 
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        <span v-html="tab.icon" style="margin-right:8px; display:inline-flex; align-items:center; vertical-align:middle"></span>
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 4" :key="i" class="skeleton stat-skeleton"></div>
    </div>

    <template v-else>
      <!-- TAB: REVENUE -->
      <div v-if="activeTab === 'revenue'" class="tab-content">
        <div class="stat-grid">
          <div class="stat-card analytical">
            <div class="stat-label">Tổng doanh thu</div>
            <div class="stat-value text-accent">{{ formatMoney(revenueData?.summary?.tong_doanh_thu) }}</div>
            <div class="stat-trend" :class="revenueTrend >= 0 ? 'up' : 'down'">
              {{ revenueTrend >= 0 ? '↑' : '↓' }} {{ Math.abs(revenueTrend) }}% <span class="text-muted">so với kỳ trước</span>
            </div>
          </div>
          <div class="stat-card analytical">
            <div class="stat-label">Trung bình đơn hàng</div>
            <div class="stat-value">{{ formatMoney(revenueData?.summary?.trung_binh_don) }}</div>
            <div class="stat-footer">Dựa trên {{ revenueData?.summary?.tong_don }} đơn hàng</div>
          </div>
          <div class="stat-card analytical">
            <div class="stat-label">Tỷ lệ hủy đơn</div>
            <div class="stat-value" :class="{ 'text-error': revenueData?.summary?.ty_le_huy > 5 }">{{ revenueData?.summary?.ty_le_huy || 0 }}%</div>
            <div class="progress-bar">
              <div class="progress-fill error" :style="`width: ${revenueData?.summary?.ty_le_huy}%`"></div>
            </div>
          </div>
          <div class="stat-card analytical">
            <div class="stat-label">Phương thức CK</div>
            <div class="stat-value">{{ paymentRatio }}%</div>
            <div class="progress-bar">
              <div class="progress-fill primary" :style="`width: ${paymentRatio}%`"></div>
            </div>
          </div>
        </div>

        <div class="reports-grid">
          <div class="card chart-card full-width">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
              Xu hướng doanh thu
            </h3>
            <div class="chart-container">
              <canvas ref="revenueChartRef"></canvas>
            </div>
          </div>
          
          <div class="card chart-card">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              Phân bổ doanh thu theo giờ
            </h3>
            <div class="chart-container heatmap-container">
              <canvas ref="hourlyHeatmapRef"></canvas>
            </div>
          </div>

          <div class="card">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              Tỷ trọng doanh thu theo danh mục
            </h3>
            <div class="chart-container doughnut-container">
              <canvas ref="categoryChartRef"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: PRODUCTS -->
      <div v-if="activeTab === 'products'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:#EF4444"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
              Phân tích hiệu suất món ăn
            </h3>
            <div class="card-actions">
              <input type="text" v-model="productSearch" placeholder="Tìm kiếm món..." class="filter-input">
            </div>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Hạng</th>
                  <th>Tên món</th>
                  <th>Danh mục</th>
                  <th class="text-right">Số lượng bán</th>
                  <th class="text-right">Doanh thu</th>
                  <th>Tỷ lệ đóng góp</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(mon, idx) in filteredProducts" :key="mon.id">
                  <td>
                    <span v-if="idx < 3" class="rank-badge" :class="'rank-' + (idx + 1)">{{ idx + 1 }}</span>
                    <span v-else class="rank-text">{{ idx + 1 }}</span>
                  </td>
                  <td class="font-medium">{{ mon.ten_mon }}</td>
                  <td><span class="badge badge-secondary">{{ mon.danh_muc }}</span></td>
                  <td class="text-right"><b>{{ mon.tong_ban }}</b></td>
                  <td class="text-right text-accent">{{ formatMoney(mon.tong_doanh_thu_mon) }}</td>
                  <td>
                    <div class="table-progress">
                      <div class="progress-fill" :style="`width: ${(mon.tong_doanh_thu_mon / (revenueData?.summary?.tong_doanh_thu || 1)) * 100}%`"></div>
                      <span class="progress-text">{{ ((mon.tong_doanh_thu_mon / (revenueData?.summary?.tong_doanh_thu || 1)) * 100).toFixed(1) }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: ORDERS -->
      <div v-if="activeTab === 'orders'" class="tab-content">
        <div class="reports-grid">
          <div class="card">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--coffee-accent)"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
              Thống kê trạng thái đơn
            </h3>
            <div class="status-summary">
              <div v-for="(val, key) in ordersData?.by_status" :key="key" class="status-stat-item">
                <span class="status-dot" :class="key"></span>
                <span class="status-label">{{ formatStatusKey(key) }}</span>
                <span class="status-value">{{ val }}</span>
              </div>
            </div>
          </div>

          <div class="card">
            <h3>💳 Phương thức thanh toán</h3>
            <div class="payment-summary">
              <div class="payment-item">
                <div class="payment-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                </div>
                <div class="payment-info">
                  <span>Tiền mặt</span>
                  <b>{{ ordersData?.by_payment?.tien_mat || 0 }} đơn</b>
                </div>
              </div>
              <div class="payment-item">
                <div class="payment-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                </div>
                <div class="payment-info">
                  <span>Chuyển khoản</span>
                  <b>{{ ordersData?.by_payment?.chuyen_khoan || 0 }} đơn</b>
                </div>
              </div>
            </div>
          </div>

          <div class="card full-width" v-if="ordersData?.cancel_reasons?.length">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle; margin-right:8px; color:var(--error)"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              Lý do hủy đơn phổ biến
            </h3>
            <div class="reasons-grid">
              <div v-for="cr in ordersData.cancel_reasons" :key="cr.ly_do" class="reason-card">
                <div class="reason-count">{{ cr.so_luong }}</div>
                <div class="reason-text">{{ cr.ly_do }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue'
import api from '@/services/api'
import { Chart, registerables } from 'chart.js'
import * as XLSX from 'xlsx'
Chart.register(...registerables)

const loading = ref(true)
const dateRange = ref('30days')
const activeTab = ref('revenue')
const productSearch = ref('')

const tabs = [
  { 
    id: 'revenue', 
    label: 'Doanh thu', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
  },
  { 
    id: 'products', 
    label: 'Sản phẩm', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path><line x1="6" y1="2" x2="6" y2="4"></line><line x1="10" y1="2" x2="10" y2="4"></line><line x1="14" y1="2" x2="14" y2="4"></line></svg>'
  },
  { 
    id: 'orders', 
    label: 'Đơn hàng', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>'
  }
]

const revenueData = ref(null)
const bestSellersData = ref(null)
const ordersData = ref(null)

const revenueChartRef = ref(null)
const categoryChartRef = ref(null)
const hourlyHeatmapRef = ref(null)

let chartInstances = []

// Watcher để vẽ lại biểu đồ khi chuyển tab
watch(activeTab, async (newTab) => {
  if (newTab === 'revenue') {
    await nextTick()
    buildCharts()
  }
})

const revenueTrend = ref(12.5) // Giả định

const paymentRatio = computed(() => {
  if (!ordersData.value?.by_payment) return 0
  const total = (ordersData.value.by_payment.tien_mat || 0) + (ordersData.value.by_payment.chuyen_khoan || 0)
  if (total === 0) return 0
  return Math.round((ordersData.value.by_payment.chuyen_khoan / total) * 100)
})

const filteredProducts = computed(() => {
  if (!bestSellersData.value?.top_mons) return []
  if (!productSearch.value) return bestSellersData.value.top_mons
  const s = productSearch.value.toLowerCase()
  return bestSellersData.value.top_mons.filter(p => p.ten_mon.toLowerCase().includes(s))
})

function formatMoney(v) {
  if (!v && v !== 0) return '0đ'
  return Number(v).toLocaleString('vi-VN') + 'đ'
}

function formatStatusKey(k) {
  const m = { cho_xu_ly: 'Chờ xử lý', dang_pha: 'Đang pha', hoan_thanh: 'Hoàn thành', da_huy: 'Đã hủy' }
  return m[k] || k
}

function getDates() {
  const end = new Date()
  let start = new Date()
  
  if (dateRange.value === 'today') {
    start = new Date(end.setHours(0,0,0,0))
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
    end_date: formatLocal(new Date())
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
  // Clear old instances
  chartInstances.forEach(i => i.destroy())
  chartInstances = []

  if (activeTab.value === 'revenue') {
    // Revenue Trend Chart
    if (revenueChartRef.value && revenueData.value?.chart_data) {
      const chartData = revenueData.value.chart_data
      const instance = new Chart(revenueChartRef.value, {
        type: 'line',
        data: {
          labels: chartData.map(d => d.date),
          datasets: [{
            label: 'Doanh thu',
            data: chartData.map(d => d.revenue),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f59e0b',
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
            y: { grid: { color: 'rgba(148,163,184,0.08)' }, ticks: { color: '#94a3b8', callback: v => (v/1000) + 'k' } }
          }
        }
      })
      chartInstances.push(instance)
    }

    // Hourly Heatmap (Bar Chart)
    if (hourlyHeatmapRef.value && revenueData.value?.hourly_distribution) {
      const hData = revenueData.value.hourly_distribution
      const hours = Array.from({length: 24}, (_, i) => i)
      const values = hours.map(h => {
        const found = hData.find(d => parseInt(d.hour) === h)
        return found ? found.revenue : 0
      })

      const instance = new Chart(hourlyHeatmapRef.value, {
        type: 'bar',
        data: {
          labels: hours.map(h => h + 'h'),
          datasets: [{
            data: values,
            backgroundColor: values.map(v => {
              const opacity = Math.max(0.2, Math.min(1, v / (Math.max(...values) || 1)))
              return `rgba(245, 158, 11, ${opacity})`
            }),
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 } } },
            y: { display: false }
          }
        }
      })
      chartInstances.push(instance)
    }

    // Category Chart
    if (categoryChartRef.value && bestSellersData.value?.danh_muc_chart) {
      const dMap = bestSellersData.value.danh_muc_chart
      const instance = new Chart(categoryChartRef.value, {
        type: 'doughnut',
        data: {
          labels: Object.keys(dMap),
          datasets: [{
            data: Object.values(dMap),
            backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b'],
            borderWidth: 0,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', usePointStyle: true, padding: 20 } } }
        }
      })
      chartInstances.push(instance)
    }
  }
}

function exportData() {
  if (!revenueData.value || !ordersData.value) {
    alert('Đang tải dữ liệu, vui lòng đợi trong giây lát...')
    return
  }

  try {
    const wb = XLSX.utils.book_new()

    // 1. Sheet Tổng quan
    const summaryData = [
      ['BÁO CÁO TỔNG QUAN KINH DOANH'],
      ['Khoảng thời gian', dateRange.value === 'today' ? 'Hôm nay' : 
                          dateRange.value === '7days' ? '7 ngày qua' : 
                          dateRange.value === '30days' ? '30 ngày qua' : 'Tháng này'],
      ['Ngày xuất báo cáo', new Date().toLocaleString('vi-VN')],
      [''],
      ['CHỈ SỐ TỔNG QUAN', 'GIÁ TRỊ'],
      ['Tổng doanh thu', revenueData.value?.summary?.tong_doanh_thu || 0],
      ['Tổng đơn hàng', revenueData.value?.summary?.tong_don || 0],
      ['Trung bình đơn', revenueData.value?.summary?.trung_binh_don || 0],
      ['Tỷ lệ hủy đơn', (revenueData.value?.summary?.ty_le_huy || 0) + '%'],
      [''],
      ['TRẠNG THÁI ĐƠN HÀNG', 'SỐ LƯỢNG'],
      ['Chờ xử lý', ordersData.value?.by_status?.cho_xu_ly || 0],
      ['Đang pha', ordersData.value?.by_status?.dang_pha || 0],
      ['Hoàn thành', ordersData.value?.by_status?.hoan_thanh || 0],
      ['Đã hủy', ordersData.value?.by_status?.da_huy || 0],
      [''],
      ['PHƯƠNG THỨC THANH TOÁN', 'SỐ LƯỢNG'],
      ['Tiền mặt', ordersData.value?.by_payment?.tien_mat || 0],
      ['Chuyển khoản', ordersData.value?.by_payment?.chuyen_khoan || 0],
    ]
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData)
    XLSX.utils.book_append_sheet(wb, wsSummary, "Tong quan")

    // 2. Sheet Sản phẩm bán chạy
    if (bestSellersData.value?.top_mons) {
      const productHeader = [['Hạng', 'Tên món', 'Danh mục', 'Số lượng bán', 'Doanh thu (VNĐ)']]
      const productRows = bestSellersData.value.top_mons.map((mon, idx) => [
        idx + 1,
        mon.ten_mon,
        mon.danh_muc,
        mon.tong_ban,
        mon.tong_doanh_thu_mon
      ])
      const wsProducts = XLSX.utils.aoa_to_sheet([...productHeader, ...productRows])
      XLSX.utils.book_append_sheet(wb, wsProducts, "Top san pham")
    }

    // 3. Sheet Chi tiết doanh thu theo ngày
    if (revenueData.value?.chart_data) {
      const dailyHeader = [['Ngày', 'Doanh thu (VNĐ)']]
      const dailyRows = revenueData.value.chart_data.map(d => [d.date, d.revenue])
      const wsDaily = XLSX.utils.aoa_to_sheet([...dailyHeader, ...dailyRows])
      XLSX.utils.book_append_sheet(wb, wsDaily, "Doanh thu theo ngay")
    }

    // Tự động điều chỉnh độ rộng cột cơ bản (tuỳ chọn)
    // wsSummary['!cols'] = [{ wch: 25 }, { wch: 20 }]

    // Xuất file
    const dateStr = new Date().toISOString().split('T')[0]
    const fileName = `Bao_cao_GunplaCoffee_${dateRange.value}_${dateStr}.xlsx`
    XLSX.writeFile(wb, fileName)

  } catch (error) {
    console.error('Export failed:', error)
    alert('Có lỗi xảy ra khi xuất báo cáo: ' + error.message)
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.reports-view { display: flex; flex-direction: column; gap: 24px; }

.analysis-toolbar { display: flex; justify-content: space-between; align-items: flex-end; }
.analysis-toolbar h1 { font-size: 1.8rem; margin-bottom: 4px; }

.date-range-picker { display: flex; gap: 16px; align-items: center; }
.picker-group { display: flex; flex-direction: column; gap: 6px; }
.picker-group label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }

.reports-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 2px; }
.tab-btn { padding: 12px 24px; background: transparent; border: none; border-bottom: 3px solid transparent; color: var(--text-muted); font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 0.95rem; }
.tab-btn:hover { color: var(--text-primary); }
.tab-btn.active { border-color: var(--accent); color: var(--accent); }

.tab-content { animation: fadeIn 0.4s ease; }

.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card.analytical { background: var(--bg-card); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--radius-lg); }
.stat-card .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px; }
.stat-card .stat-value { font-size: 1.8rem; font-weight: 800; font-family: 'Outfit'; margin-bottom: 8px; }
.stat-trend { font-size: 0.85rem; font-weight: 600; }
.stat-trend.up { color: var(--success); }
.stat-trend.down { color: var(--error); }
.stat-footer { font-size: 0.75rem; color: var(--text-muted); }

.progress-bar { height: 6px; background: var(--bg-secondary); border-radius: 3px; overflow: hidden; margin-top: 12px; }
.progress-fill { height: 100%; border-radius: 3px; }
.progress-fill.primary { background: var(--primary); }
.progress-fill.error { background: var(--error); }

.reports-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
.full-width { grid-column: 1 / -1; }
.chart-container { height: 320px; position: relative; }
.heatmap-container { height: 280px; }
.doughnut-container { height: 300px; }

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.filter-input { background: var(--bg-secondary); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; color: var(--text-primary); min-width: 250px; }

.rank-badge { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 800; font-size: 0.8rem; }
.rank-1 { background: #ffd700; color: #000; }
.rank-2 { background: #c0c0c0; color: #000; }
.rank-3 { background: #cd7f32; color: #fff; }
.rank-text { width: 28px; text-align: center; color: var(--text-muted); }

.table-progress { display: flex; align-items: center; gap: 12px; }
.table-progress .progress-fill { height: 8px; background: var(--accent); flex: 1; }
.progress-text { font-size: 0.75rem; color: var(--text-muted); width: 40px; }

.status-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 10px; }
.status-stat-item { display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--bg-secondary); border-radius: 12px; }
.status-dot { width: 12px; height: 12px; border-radius: 50%; }
.status-dot.cho_xu_ly { background: var(--error); }
.status-dot.dang_pha { background: var(--warning); }
.status-dot.hoan_thanh { background: var(--success); }
.status-dot.da_huy { background: var(--text-muted); }
.status-label { flex: 1; font-weight: 500; }
.status-value { font-family: 'Outfit'; font-weight: 800; font-size: 1.2rem; }

.payment-summary { display: flex; flex-direction: column; gap: 12px; }
.payment-item { display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--bg-secondary); border-radius: 12px; }
.payment-icon { font-size: 2rem; }
.payment-info { display: flex; flex-direction: column; }
.payment-info span { font-size: 0.85rem; color: var(--text-muted); }
.payment-info b { font-size: 1.1rem; }

.reasons-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.reason-card { padding: 16px; background: var(--bg-secondary); border-radius: 12px; border-left: 4px solid var(--error); }
.reason-count { font-size: 1.5rem; font-weight: 800; color: var(--error); font-family: 'Outfit'; }
.reason-text { font-size: 0.9rem; margin-top: 4px; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1000px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .reports-grid { grid-template-columns: 1fr; }
}
</style>
