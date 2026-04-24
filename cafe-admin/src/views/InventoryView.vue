<template>
  <div class="inventory-page fade-in">
    <div class="tabs">
      <button class="tab-btn" :class="{ active: tab === 'list' }" @click="tab = 'list'">Nguyên liệu</button>
      <button class="tab-btn" :class="{ active: tab === 'history' }" @click="tab = 'history'; loadHistory()">Lịch sử kho</button>
    </div>

    <div v-if="tab === 'list'">
      <div class="toolbar">
        <div class="toolbar-actions">
          <input v-model="search" placeholder="Tìm nguyên liệu..." class="search-input" @input="load" />
          <select v-model="filter" class="filter-select" @change="load">
            <option value="">Tất cả</option><option value="het_hang">Hết hàng</option><option value="sap_het">Sắp hết</option><option value="con_hang">Còn hàng</option>
          </select>
        </div>
        <button class="btn btn-primary" @click="openForm()">+ Thêm</button>
      </div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên</th><th>Tồn kho</th><th>Đơn vị</th><th>Mức cảnh báo</th><th>Tình trạng</th><th>Thao tác</th></tr></thead>
        <tbody>
          <tr v-for="nl in items" :key="nl.ma_nguyen_lieu">
            <td style="color:var(--text-primary)">{{ nl.ma_nguyen_lieu }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ nl.ten_nguyen_lieu }}</td>
            <td :style="{ color: nl.tinh_trang_kho === 'het_hang' ? 'var(--error)' : nl.tinh_trang_kho === 'sap_het' ? 'var(--warning)' : 'var(--success)', fontWeight: 600 }">{{ nl.ton_kho }}</td>
            <td>{{ nl.don_vi_tinh }}</td><td>{{ nl.muc_canh_bao }}</td>
            <td><span :class="'badge badge-' + (nl.tinh_trang_kho === 'het_hang' ? 'error' : nl.tinh_trang_kho === 'sap_het' ? 'warning' : 'success')">{{ nl.tinh_trang_kho === 'het_hang' ? 'Hết hàng' : nl.tinh_trang_kho === 'sap_het' ? 'Sắp hết' : 'Còn hàng' }}</span></td>
            <td class="action-cell">
              <button class="btn btn-primary btn-sm" @click="openNhapKho(nl)">📥 Nhập</button>
              <button class="btn btn-ghost btn-sm" @click="openForm(nl)">Sửa</button>
              <button class="btn btn-danger btn-sm" @click="del(nl)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="tab === 'history'">
      <div class="toolbar">
        <div class="toolbar-actions">
          <select v-model="histFilter" class="filter-select" @change="loadHistory"><option value="">Tất cả loại</option><option value="NHAP_KHO">Nhập kho</option><option value="XUAT_BAN">Xuất bán</option></select>
          <input type="date" v-model="histFrom" class="search-input" @change="loadHistory" />
          <input type="date" v-model="histTo" class="search-input" @change="loadHistory" />
        </div>
      </div>
      <table class="data-table">
        <thead><tr><th>Thời gian</th><th>Nguyên liệu</th><th>Loại</th><th>Số lượng</th><th>Nhân viên</th></tr></thead>
        <tbody>
          <tr v-for="h in history" :key="h.ma_ls_kho">
            <td>{{ h.thoi_gian }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ h.ten_nguyen_lieu }}</td>
            <td><span :class="h.loai_giao_dich === 'NHAP_KHO' ? 'badge badge-success' : 'badge badge-warning'">{{ h.loai_giao_dich === 'NHAP_KHO' ? 'Nhập kho' : 'Xuất bán' }}</span></td>
            <td :style="{ color: h.so_luong_thay_doi > 0 ? 'var(--success)' : 'var(--error)', fontWeight: 600 }">{{ h.so_luong_thay_doi > 0 ? '+' : '' }}{{ h.so_luong_thay_doi }} {{ h.don_vi_tinh }}</td>
            <td>{{ h.nhan_vien }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box card">
        <h3>{{ editing ? 'Sửa nguyên liệu' : 'Thêm nguyên liệu' }}</h3>
        <div class="modal-body">
          <div class="modal-form">
            <div class="form-group"><label>Mã</label><input v-model="form.ma_nguyen_lieu" :disabled="editing" /></div>
            <div class="form-group"><label>Tên</label><input v-model="form.ten_nguyen_lieu" /></div>
            <div class="form-group"><label>Đơn vị tính</label><input v-model="form.don_vi_tinh" /></div>
            <div class="form-group"><label>Tồn kho ban đầu</label><input v-model.number="form.ton_kho" type="number" :disabled="editing" /></div>
            <div class="form-group"><label>Mức cảnh báo</label><input v-model.number="form.muc_canh_bao" type="number" /></div>
            <div class="form-group"><label>Trạng thái</label><select v-model="form.trang_thai"><option value="hoat_dong">Hoạt động</option><option value="ngung">Ngừng</option></select></div>
          </div>
        </div>
        <div v-if="formErr" class="error-msg">{{ formErr }}</div>
        <div class="modal-actions"><button class="btn btn-ghost" @click="showModal = false">Hủy</button><button class="btn btn-primary" @click="save">Lưu</button></div>
      </div>
    </div>
    </Teleport>

    <Teleport to="body">
    <div v-if="showNhapKho" class="modal-overlay" @click.self="showNhapKho = false">
      <div class="modal-box card" style="max-width:400px">
        <h3>📥 Nhập kho: {{ nhapKhoItem?.ten_nguyen_lieu }}</h3>
        <div class="modal-form"><div class="form-group"><label>Số lượng nhập</label><input v-model.number="nhapSoLuong" type="number" min="0.01" step="0.01" /></div></div>
        <div class="modal-actions"><button class="btn btn-ghost" @click="showNhapKho = false">Hủy</button><button class="btn btn-primary" @click="nhapKho">Nhập kho</button></div>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const { confirm } = useConfirm()

const tab = ref('list'), items = ref([]), search = ref(''), filter = ref('')
const history = ref([]), histFilter = ref(''), histFrom = ref(''), histTo = ref('')
const showModal = ref(false), editing = ref(false), form = ref({}), formErr = ref('')
const showNhapKho = ref(false), nhapKhoItem = ref(null), nhapSoLuong = ref(0)

async function load() { try { const r = await api.getNguyenLieu({ search: search.value, tinh_trang_kho: filter.value }); items.value = r.data.data } catch(e) { console.error(e) } }
async function loadHistory() { try { const r = await api.getLichSuKho({ loai_giao_dich: histFilter.value, tu_ngay: histFrom.value, den_ngay: histTo.value }); history.value = r.data.data } catch(e) { console.error(e) } }

function openForm(nl) {
  editing.value = !!nl; formErr.value = ''
  form.value = nl ? { ...nl } : { ma_nguyen_lieu: '', ten_nguyen_lieu: '', don_vi_tinh: '', ton_kho: 0, muc_canh_bao: 0, trang_thai: 'hoat_dong' }
  showModal.value = true
}
async function save() {
  try {
    if (editing.value) await api.updateNguyenLieu(form.value.ma_nguyen_lieu, form.value)
    else await api.createNguyenLieu(form.value)
    showModal.value = false; toast.success(editing.value ? 'Cập nhật thành công!' : 'Thêm thành công!'); await load()
  } catch(e) { formErr.value = e.response?.data?.message || 'Lỗi' }
}
async function del(nl) {
  const ok = await confirm(`Bạn có chắc muốn xóa "${nl.ten_nguyen_lieu}"?`, 'Xóa nguyên liệu')
  if (!ok) return
  try { await api.deleteNguyenLieu(nl.ma_nguyen_lieu); toast.success('Đã xóa!'); await load() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}
function openNhapKho(nl) { nhapKhoItem.value = nl; nhapSoLuong.value = 0; showNhapKho.value = true }
async function nhapKho() {
  if (nhapSoLuong.value <= 0) { toast.warning('Số lượng phải lớn hơn 0'); return }
  try {
    await api.nhapKho(nhapKhoItem.value.ma_nguyen_lieu, { so_luong: nhapSoLuong.value })
    showNhapKho.value = false; toast.success('Nhập kho thành công!'); await load()
  } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}
onMounted(load)
</script>

<style scoped>
.tabs { display:flex;gap:4px;margin-bottom:24px;background:var(--bg-secondary);border-radius:var(--radius-lg);padding:4px;width:fit-content }
.tab-btn { padding:10px 24px;border:none;background:none;color:var(--text-secondary);font-weight:500;border-radius:var(--radius-md);cursor:pointer;transition:all var(--transition-fast) }
.tab-btn.active { background:var(--accent);color:#0f172a;font-weight:600 }
.toolbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px }
.toolbar-actions { display:flex;gap:10px }
.search-input { width:180px;padding:8px 12px }
.filter-select { width:160px;padding:8px 12px }
.action-cell { display:flex;gap:6px }
</style>
