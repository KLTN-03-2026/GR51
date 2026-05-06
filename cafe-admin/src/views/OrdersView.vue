<template>
  <div class="orders-page fade-in">
    <div class="toolbar">
      <div class="toolbar-actions">
        <input v-model="filters.search" placeholder="Mã đơn..." class="search-input" @input="load" />
        <input type="date" v-model="filters.tu_ngay" class="search-input" @change="load" />
        <input type="date" v-model="filters.den_ngay" class="search-input" @change="load" />
        <select v-model="filters.trang_thai_don" class="filter-select" @change="load">
          <option value="">Trạng thái</option>
          <option :value="0">Chờ xác nhận</option>
          <option :value="1">Đang pha</option>
          <option :value="2">Hoàn thành</option>
          <option :value="3">Đã hủy</option>
        </select>
        <select v-model="filters.trang_thai_thanh_toan" class="filter-select" @change="load">
          <option value="">Thanh toán</option>
          <option :value="1">Đã TT</option>
          <option :value="0">Chưa TT</option>
        </select>
      </div>
    </div>
    <div class="summary-row" v-if="summary">
      <div class="summary-card"><span class="s-label">Tổng đơn</span><span class="s-val">{{ summary.tong_don }}</span></div>
      <div class="summary-card"><span class="s-label">Doanh thu</span><span class="s-val accent">{{ fmt(summary.tong_doanh_thu) }}</span></div>
    </div>
    <table class="data-table">
      <thead><tr><th>Mã</th><th>Bàn</th><th>NV</th><th>Tổng</th><th>TT</th><th>Trạng thái</th><th>Giờ</th><th></th></tr></thead>
      <tbody>
        <tr v-for="d in orders" :key="d.ma_don_hang">
          <td style="color:var(--text-primary);font-weight:500">{{ d.ma_don_hang.slice(-8) }}</td>
          <td>{{ d.ten_ban }}</td><td>{{ d.nhan_vien }}</td>
          <td style="color:var(--accent);font-weight:600">{{ fmt(d.tong_tien) }}</td>
          <td><span :class="d.trang_thai_thanh_toan === 1 ? 'badge badge-success' : 'badge badge-error'">{{ d.trang_thai_thanh_toan === 1 ? 'Đã TT' : 'Chưa' }}</span></td>
          <td><span class="badge" :class="{'badge-info': d.trang_thai_don === 0, 'badge-warning': d.trang_thai_don === 1, 'badge-success': d.trang_thai_don === 2, 'badge-error': d.trang_thai_don === 3}">{{ {0: 'Chờ', 1: 'Đang pha', 2: 'Hoàn thành', 3: 'Đã hủy'}[d.trang_thai_don] || d.trang_thai_don }}</span></td>
          <td>{{ d.thoi_gian }}</td>
          <td><button class="btn btn-ghost btn-sm" @click="detail=d">Xem</button></td>
        </tr>
      </tbody>
    </table>
    <Teleport to="body">
    <div v-if="detail" class="modal-overlay" @click.self="detail=null">
      <div class="modal-box card">
        <h3>Đơn {{ detail.ma_don_hang.slice(-8) }}</h3>
        <p style="color:var(--text-secondary);margin-bottom:12px">{{ detail.ten_ban }} | {{ detail.nhan_vien }} | {{ detail.thoi_gian }}</p>
        <table class="data-table"><thead><tr><th>Món</th><th>SL</th><th>Giá</th></tr></thead>
          <tbody><tr v-for="c in detail.chi_tiets" :key="c.ma_chi_tiet"><td style="color:var(--text-primary)">{{ c.ten_mon }}</td><td>{{ c.so_luong }}</td><td style="color:var(--accent)">{{ fmt(c.don_gia) }}</td></tr></tbody>
        </table>
        <p style="text-align:right;margin-top:12px;font-weight:700;color:var(--accent)">Tổng: {{ fmt(detail.tong_tien) }}</p>
        <div v-if="detail.trang_thai_don === 3 && detail.ly_do_huy" class="cancel-reason">
          <strong>Lý do hủy:</strong> {{ detail.ly_do_huy }}
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px"><button class="btn btn-ghost" @click="detail=null">Đóng</button></div>
      </div>
    </div>
    </Teleport>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
const orders = ref([]), summary = ref(null), detail = ref(null)
const filters = ref({ search:'', tu_ngay:'', den_ngay:'', trang_thai_don:'', trang_thai_thanh_toan:'' })
const fmt = v => v ? Number(v).toLocaleString('vi-VN')+'đ' : '0đ'
async function load() { try { const r = await api.getDonHang(filters.value); orders.value = r.data.data.don_hangs; summary.value = r.data.data.tong_hop } catch(e) { console.error(e) } }
onMounted(load)
</script>
<style scoped>
.toolbar { margin-bottom: 20px; }
.toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.search-input { width: 150px; padding: 8px 12px; }
.filter-select { width: 150px; padding: 8px 12px; }
.summary-row { display: flex; gap: 16px; margin-bottom: 20px; }
.summary-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 16px 24px; display: flex; flex-direction: column; gap: 4px; }
.s-label { font-size: 0.8rem; color: var(--text-muted); }
.s-val { font-family: 'Outfit'; font-size: 1.3rem; font-weight: 700; }
.s-val.accent { color: var(--accent); }
.cancel-reason {
  margin-top: 12px;
  padding: 10px;
  background: rgba(var(--error-rgb, 255, 0, 0), 0.1);
  border-left: 3px solid var(--error);
  color: var(--error);
  font-size: 0.9rem;
}
</style>
