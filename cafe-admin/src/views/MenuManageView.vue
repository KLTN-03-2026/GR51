<template>
  <div class="menu-page fade-in">
    <!-- Tabs -->
    <div class="tabs">
      <button v-for="tab in tabs" :key="tab.key" class="tab-btn" :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">{{ tab.label }}</button>
    </div>

    <!-- TAB: Danh mục -->
    <div v-if="activeTab === 'danhmuc'" class="tab-content">
      <div class="toolbar"><h3>Danh mục ({{ danhMucs.length }})</h3><button class="btn btn-primary" @click="openDM()">+ Thêm</button></div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên danh mục</th><th>Số món</th><th>Thao tác</th></tr></thead>
        <tbody>
          <tr v-for="dm in danhMucs" :key="dm.id">
            <td style="color:var(--text-primary)">{{ dm.ma_danh_muc }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ dm.ten_danh_muc }}</td>
            <td>{{ dm.mons_count || 0 }}</td>
            <td class="action-cell">
              <button class="btn btn-ghost btn-sm" @click="openDM(dm)">Sửa</button>
              <button class="btn btn-danger btn-sm" @click="deleteDM(dm)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- TAB: Món ăn -->
    <div v-if="activeTab === 'mon'" class="tab-content">
      <div class="toolbar">
        <h3>Món ăn ({{ mons.length }})</h3>
        <div class="toolbar-actions">
          <input v-model="monSearch" placeholder="Tìm kiếm..." class="search-input" @input="loadMons" />
          <select v-model="monFilter" class="filter-select" @change="loadMons">
            <option value="">Tất cả danh mục</option>
            <option v-for="dm in danhMucs" :key="dm.id" :value="dm.id">{{ dm.ten_danh_muc }}</option>
          </select>
          <button class="btn btn-primary" @click="openMon()">+ Thêm</button>
        </div>
      </div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên món</th><th>Danh mục</th><th>Giá bán</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
          <tr v-for="m in mons" :key="m.id">
            <td style="color:var(--text-primary)">{{ m.ma_mon }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ m.ten_mon }}</td>
            <td>{{ m.ten_danh_muc }}</td>
            <td style="color:var(--accent)">{{ formatMoney(m.gia_ban) }}</td>
            <td><span :class="m.trang_thai === 1 ? 'badge badge-success' : 'badge badge-error'">{{ m.trang_thai === 1 ? 'Đang bán' : 'Ngừng bán' }}</span></td>
            <td class="action-cell">
              <button class="btn btn-ghost btn-sm" @click="openMon(m)">Sửa</button>
              <button class="btn btn-danger btn-sm" @click="deleteMon(m)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- TAB: Kích cỡ -->
    <div v-if="activeTab === 'kichco'" class="tab-content">
      <div class="toolbar"><h3>Kích cỡ ({{ kichCos.length }})</h3><button class="btn btn-primary" @click="openKC()">+ Thêm</button></div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên</th><th>Giá cộng thêm</th><th>Thao tác</th></tr></thead>
        <tbody>
          <tr v-for="kc in kichCos" :key="kc.id">
            <td style="color:var(--text-primary)">{{ kc.ma_kich_co }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ kc.ten_kich_co }}</td>
            <td style="color:var(--accent)">{{ formatMoney(kc.gia_cong_them) }}</td>
            <td class="action-cell">
              <button class="btn btn-ghost btn-sm" @click="openKC(kc)">Sửa</button>
              <button class="btn btn-danger btn-sm" @click="deleteKC(kc)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- TAB: Topping -->
    <div v-if="activeTab === 'topping'" class="tab-content">
      <div class="toolbar"><h3>Topping ({{ toppings.length }})</h3><button class="btn btn-primary" @click="openTP()">+ Thêm</button></div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên</th><th>Giá</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
          <tr v-for="tp in toppings" :key="tp.id">
            <td style="color:var(--text-primary)">{{ tp.ma_topping }}</td>
            <td style="color:var(--text-primary);font-weight:500">{{ tp.ten_topping }}</td>
            <td style="color:var(--accent)">{{ formatMoney(tp.gia_tien) }}</td>
            <td><span :class="tp.trang_thai === 1 ? 'badge badge-success' : 'badge badge-error'">{{ tp.trang_thai === 1 ? 'Hoạt động' : 'Ngừng' }}</span></td>
            <td class="action-cell">
              <button class="btn btn-ghost btn-sm" @click="openTP(tp)">Sửa</button>
              <button class="btn btn-danger btn-sm" @click="deleteTP(tp)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- TAB: Công thức -->
    <div v-if="activeTab === 'congthuc'" class="tab-content">
      <div class="toolbar">
        <h3>Công thức pha chế</h3>
        <select v-model="selectedMon" class="filter-select" @change="loadCongThuc">
          <option value="">-- Chọn món --</option>
          <option v-for="m in mons" :key="m.id" :value="m.id">{{ m.ten_mon }}</option>
        </select>
      </div>
      <div v-if="congThuc" class="card" style="margin-top:16px">
        <h4 style="margin-bottom:12px;color:var(--accent)">{{ congThuc.ten_mon }}</h4>
        <p v-if="congThuc.huong_dan" style="margin-bottom:16px;color:var(--text-secondary);white-space:pre-line">{{ congThuc.huong_dan }}</p>
        <div v-for="(nl, idx) in congThucItems" :key="idx" class="recipe-row">
          <select v-model="nl.nguyen_lieu_id" class="filter-select" style="flex:2">
            <option value="">Chọn nguyên liệu</option>
            <option v-for="n in nguyenLieus" :key="n.id" :value="n.id">{{ n.ten_nguyen_lieu }} ({{ n.don_vi_tinh }})</option>
          </select>
          <input v-model.number="nl.so_luong_can" type="number" step="0.01" placeholder="Số lượng" style="flex:1" />
          <button class="btn btn-danger btn-icon" @click="congThucItems.splice(idx, 1)">✕</button>
        </div>
        <div style="display:flex;gap:10px;margin-top:12px">
          <button class="btn btn-ghost btn-sm" @click="congThucItems.push({ nguyen_lieu_id: '', so_luong_can: 0 })">+ Thêm NL</button>
          <button class="btn btn-primary btn-sm" @click="saveCongThuc">💾 Lưu công thức</button>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-box card">
          <h3>{{ modalTitle }}</h3>
          <div class="modal-body">
            <div class="modal-form">
              <div v-for="f in modalFields" :key="f.key" class="form-group">
                <label>{{ f.label }}</label>
                <select v-if="f.type === 'select'" v-model="modalData[f.key]">
                  <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
                <textarea v-else-if="f.type === 'textarea'" v-model="modalData[f.key]" rows="3"></textarea>
                <input v-else v-model="modalData[f.key]" :type="f.type || 'text'" :disabled="f.disabled" />
              </div>
            </div>
          </div>
          <div v-if="modalError" class="error-msg">{{ modalError }}</div>
          <div class="modal-actions">
            <button class="btn btn-ghost" @click="showModal = false">Hủy</button>
            <button class="btn btn-primary" @click="saveModal" :disabled="modalSaving">{{ modalSaving ? 'Đang lưu...' : 'Lưu' }}</button>
          </div>
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

const tabs = [
  { key: 'danhmuc', label: 'Danh mục' },
  { key: 'mon', label: 'Món ăn' },
  { key: 'kichco', label: 'Kích cỡ' },
  { key: 'topping', label: 'Topping' },
  { key: 'congthuc', label: 'Công thức' },
]
const activeTab = ref('danhmuc')
const danhMucs = ref([]), mons = ref([]), kichCos = ref([]), toppings = ref([]), nguyenLieus = ref([])
const monSearch = ref(''), monFilter = ref('')
const selectedMon = ref(''), congThuc = ref(null), congThucItems = ref([])
const showModal = ref(false), modalTitle = ref(''), modalFields = ref([]), modalData = ref({}), modalError = ref(''), modalSaving = ref(false)
let modalSaveFn = null

function formatMoney(v) { return v ? Number(v).toLocaleString('vi-VN') + 'đ' : '0đ' }

async function loadDanhMucs() { try { const r = await api.getDanhMuc(); danhMucs.value = r.data.data } catch(e) { console.error(e) } }
async function loadMons() { try { const r = await api.getMon({ search: monSearch.value, danh_muc_id: monFilter.value }); mons.value = r.data.data } catch(e) { console.error(e) } }
async function loadKichCos() { try { const r = await api.getKichCo(); kichCos.value = r.data.data } catch(e) { console.error(e) } }
async function loadToppings() { try { const r = await api.getTopping(); toppings.value = r.data.data } catch(e) { console.error(e) } }
async function loadNguyenLieus() { try { const r = await api.getNguyenLieu(); nguyenLieus.value = r.data.data } catch(e) { console.error(e) } }

function openDM(dm) {
  modalTitle.value = dm ? 'Sửa danh mục' : 'Thêm danh mục'
  modalData.value = dm ? { ma_danh_muc: dm.ma_danh_muc, ten_danh_muc: dm.ten_danh_muc } : { ma_danh_muc: '', ten_danh_muc: '' }
  modalFields.value = [{ key: 'ma_danh_muc', label: 'Mã danh mục', disabled: !!dm },{ key: 'ten_danh_muc', label: 'Tên danh mục' }]
  modalError.value = ''
  modalSaveFn = async () => { if (dm) await api.updateDanhMuc(dm.id, modalData.value); else await api.createDanhMuc(modalData.value); await loadDanhMucs(); toast.success(dm ? 'Cập nhật danh mục thành công!' : 'Thêm danh mục thành công!') }
  showModal.value = true
}
async function deleteDM(dm) {
  const ok = await confirm(`Bạn có chắc muốn xóa danh mục "${dm.ten_danh_muc}"?`, 'Xóa danh mục')
  if (!ok) return
  try { await api.deleteDanhMuc(dm.id); toast.success('Đã xóa danh mục!'); await loadDanhMucs() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

function openMon(m) {
  modalTitle.value = m ? 'Sửa món ăn' : 'Thêm món ăn'
  modalData.value = m ? { ...m } : { ma_mon: '', danh_muc_id: '', ten_mon: '', gia_ban: 0, trang_thai: 1, hinh_anh: '', cong_thuc: '' }
  modalFields.value = [
    { key: 'ma_mon', label: 'Mã món', disabled: !!m },{ key: 'ten_mon', label: 'Tên món' },
    { key: 'danh_muc_id', label: 'Danh mục', type: 'select', options: danhMucs.value.map(d => ({ value: d.id, label: d.ten_danh_muc })) },
    { key: 'gia_ban', label: 'Giá bán', type: 'number' },
    { key: 'trang_thai', label: 'Trạng thái', type: 'select', options: [{ value: 1, label: 'Đang bán' }, { value: 0, label: 'Ngừng bán' }] },
    { key: 'hinh_anh', label: 'URL Hình ảnh' },{ key: 'cong_thuc', label: 'Hướng dẫn pha chế', type: 'textarea' },
  ]
  modalError.value = ''
  modalSaveFn = async () => { if (m) await api.updateMon(m.id, modalData.value); else await api.createMon(modalData.value); await loadMons(); toast.success(m ? 'Cập nhật món thành công!' : 'Thêm món thành công!') }
  showModal.value = true
}
async function deleteMon(m) {
  const ok = await confirm(`Bạn có chắc muốn xóa món "${m.ten_mon}"?`, 'Xóa món')
  if (!ok) return
  try { await api.deleteMon(m.id); toast.success('Đã xóa món!'); await loadMons() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

function openKC(kc) {
  modalTitle.value = kc ? 'Sửa kích cỡ' : 'Thêm kích cỡ'
  modalData.value = kc ? { ...kc } : { ma_kich_co: '', ten_kich_co: '', gia_cong_them: 0 }
  modalFields.value = [{ key: 'ma_kich_co', label: 'Mã kích cỡ', disabled: !!kc },{ key: 'ten_kich_co', label: 'Tên' },{ key: 'gia_cong_them', label: 'Giá cộng thêm', type: 'number' }]
  modalError.value = ''
  modalSaveFn = async () => { if (kc) await api.updateKichCo(kc.id, modalData.value); else await api.createKichCo(modalData.value); await loadKichCos(); toast.success(kc ? 'Cập nhật kích cỡ thành công!' : 'Thêm kích cỡ thành công!') }
  showModal.value = true
}
async function deleteKC(kc) {
  const ok = await confirm(`Bạn có chắc muốn xóa kích cỡ "${kc.ten_kich_co}"?`, 'Xóa kích cỡ')
  if (!ok) return
  try { await api.deleteKichCo(kc.id); toast.success('Đã xóa!'); await loadKichCos() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

function openTP(tp) {
  modalTitle.value = tp ? 'Sửa topping' : 'Thêm topping'
  modalData.value = tp ? { ...tp } : { ma_topping: '', ten_topping: '', gia_tien: 0, trang_thai: 1, hinh_anh: '' }
  modalFields.value = [
    { key: 'ma_topping', label: 'Mã topping', disabled: !!tp },{ key: 'ten_topping', label: 'Tên' },{ key: 'gia_tien', label: 'Giá', type: 'number' },
    { key: 'trang_thai', label: 'Trạng thái', type: 'select', options: [{ value: 1, label: 'Hoạt động' }, { value: 0, label: 'Ngừng' }] },
    { key: 'hinh_anh', label: 'URL Hình ảnh' },
  ]
  modalError.value = ''
  modalSaveFn = async () => { if (tp) await api.updateTopping(tp.id, modalData.value); else await api.createTopping(modalData.value); await loadToppings(); toast.success(tp ? 'Cập nhật topping thành công!' : 'Thêm topping thành công!') }
  showModal.value = true
}
async function deleteTP(tp) {
  const ok = await confirm(`Bạn có chắc muốn xóa topping "${tp.ten_topping}"?`, 'Xóa topping')
  if (!ok) return
  try { await api.deleteTopping(tp.id); toast.success('Đã xóa!'); await loadToppings() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

async function loadCongThuc() {
  if (!selectedMon.value) { congThuc.value = null; return }
  try {
    const r = await api.getCongThuc(selectedMon.value)
    congThuc.value = r.data.data
    congThucItems.value = (r.data.data.nguyen_lieu || []).map(nl => ({ nguyen_lieu_id: nl.id, so_luong_can: nl.so_luong_can }))
  } catch(e) { toast.error('Lỗi tải công thức') }
}
async function saveCongThuc() {
  try {
    await api.saveCongThuc({ mon_id: selectedMon.value, nguyen_lieu: congThucItems.value.filter(n => n.nguyen_lieu_id) })
    toast.success('Lưu công thức thành công!')
  } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

async function saveModal() {
  modalSaving.value = true; modalError.value = ''
  try { await modalSaveFn(); showModal.value = false } catch(e) { modalError.value = e.response?.data?.message || 'Lỗi khi lưu' } finally { modalSaving.value = false }
}

onMounted(() => { loadDanhMucs(); loadMons(); loadKichCos(); loadToppings(); loadNguyenLieus() })
</script>

<style scoped>
.tabs { display: flex; gap: 4px; margin-bottom: 24px; background: var(--bg-secondary); border-radius: var(--radius-lg); padding: 4px; }
.tab-btn { flex: 1; padding: 10px; border: none; background: none; color: var(--text-secondary); font-weight: 500; font-size: 0.88rem; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-fast); }
.tab-btn.active { background: var(--accent); color: #FFFFFF; font-weight: 600; }
.tab-btn:hover:not(.active) { background: var(--bg-tertiary); }
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar h3 { font-size: 1rem; }
.toolbar-actions { display: flex; gap: 10px; align-items: center; }
.search-input { width: 200px; padding: 8px 12px; }
.filter-select { width: 180px; padding: 8px 12px; }
.action-cell { display: flex; gap: 6px; }
.recipe-row { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; }
</style>
