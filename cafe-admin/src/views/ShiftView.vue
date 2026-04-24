<template>
  <div class="shift-page fade-in">
    <div class="toolbar">
      <div class="toolbar-actions">
        <select v-model="filterNV" class="filter-select" @change="load"><option value="">Tất cả NV</option><option v-for="ns in nhanSus" :key="ns.ma_nhan_su" :value="ns.ma_nhan_su">{{ ns.ho_ten }}</option></select>
        <select v-model="filterTT" class="filter-select" @change="load"><option value="">Trạng thái</option><option value="dang_hoat_dong">Đang mở</option><option value="da_dong">Đã đóng</option></select>
        <input type="date" v-model="tuNgay" class="search-input" @change="load" />
        <input type="date" v-model="denNgay" class="search-input" @change="load" />
      </div>
    </div>
    <table class="data-table">
      <thead><tr><th>Mã ca</th><th>Nhân viên</th><th>Bắt đầu</th><th>Kết thúc</th><th>Doanh thu</th><th>Tiền mặt đầu ca</th><th>Tiền mặt hệ thống</th><th>Tổng đơn</th><th>Trạng thái</th></tr></thead>
      <tbody><tr v-for="c in shifts" :key="c.ma_ca_lam">
        <td style="color:var(--text-primary)">{{ c.ma_ca_lam?.slice(-8) }}</td>
        <td style="color:var(--text-primary);font-weight:500">{{ c.nhan_vien }}</td>
        <td>{{ fmtTime(c.thoi_gian_bat_dau) }}</td>
        <td>{{ c.thoi_gian_ket_thuc ? fmtTime(c.thoi_gian_ket_thuc) : '—' }}</td>
        <td style="color:var(--accent);font-weight:600">{{ fmt(c.tong_doanh_thu) }}</td>
        <td>{{ fmt(c.tien_mat_dau_ca) }}</td>
        <td>{{ fmt(c.tien_mat_he_thong) }}</td>
        <td>{{ c.thong_ke?.tong_don || 0 }}</td>
        <td><span :class="c.trang_thai==='dang_hoat_dong'?'badge badge-success':'badge badge-info'">{{ c.trang_thai==='dang_hoat_dong'?'Đang mở':'Đã đóng' }}</span></td>
      </tr></tbody>
    </table>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
const shifts = ref([]), nhanSus = ref([])
const filterNV = ref(''), filterTT = ref(''), tuNgay = ref(''), denNgay = ref('')
const fmt = v => v ? Number(v).toLocaleString('vi-VN')+'đ' : '0đ'
const fmtTime = t => { if(!t) return ''; const d = new Date(t); return d.toLocaleString('vi-VN') }
async function load() { try { const r = await api.getCaLam({ ma_nhan_su:filterNV.value, trang_thai:filterTT.value, tu_ngay:tuNgay.value, den_ngay:denNgay.value }); shifts.value = r.data.data } catch(e){} }
async function loadNS() { try { const r = await api.getNhanSu(); nhanSus.value = r.data.data } catch(e){} }
onMounted(() => { load(); loadNS() })
</script>
<style scoped>
.toolbar { margin-bottom:16px } .toolbar-actions { display:flex;gap:10px;flex-wrap:wrap }
.filter-select { width:160px;padding:8px 12px } .search-input { width:150px;padding:8px 12px }
</style>
