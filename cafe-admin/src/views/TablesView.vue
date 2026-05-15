<template>
  <div class="tables-page fade-in">
    <div class="tabs">
      <button class="tab-btn" :class="{active:tab==='kv'}" @click="tab='kv'">Khu vực</button>
      <button class="tab-btn" :class="{active:tab==='ban'}" @click="tab='ban'">Bàn</button>
    </div>
    <div v-if="tab==='kv'">
      <div class="toolbar"><h3>Khu vực ({{ khuVucs.length }})</h3><button class="btn btn-primary" @click="openKV()">+ Thêm</button></div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên khu vực</th><th>Số bàn</th><th>Thao tác</th></tr></thead>
        <tbody><tr v-for="k in khuVucs" :key="k.id">
          <td style="color:var(--text-primary)">{{ k.ma_khu_vuc }}</td>
          <td style="color:var(--text-primary);font-weight:500">{{ k.ten_khu_vuc }}</td>
          <td>{{ k.bans_count || 0 }}</td>
          <td class="action-cell"><button class="btn btn-ghost btn-sm" @click="openKV(k)">Sửa</button><button class="btn btn-danger btn-sm" @click="delKV(k)">Xóa</button></td>
        </tr></tbody>
      </table>
    </div>
    <div v-if="tab==='ban'">
      <div class="toolbar">
        <h3>Bàn ({{ bans.length }})</h3>
        <div class="toolbar-actions"><select v-model="banFilter" class="filter-select" @change="loadBans"><option value="">Tất cả khu vực</option><option v-for="k in khuVucs" :key="k.id" :value="k.id">{{ k.ten_khu_vuc }}</option></select></div>
        <button class="btn btn-primary" @click="openBan()">+ Thêm</button>
      </div>
      <table class="data-table">
        <thead><tr><th>Mã</th><th>Tên bàn</th><th>Khu vực</th><th>Mã QR</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody><tr v-for="b in bans" :key="b.id">
          <td style="color:var(--text-primary)">{{ b.ma_ban }}</td>
          <td style="color:var(--text-primary);font-weight:500">{{ b.ten_ban }}</td>
          <td>{{ b.ten_khu_vuc }}</td>
          <td style="font-size:0.8rem;color:var(--text-muted)">{{ b.ma_qr }}</td>
          <td><span :class="b.trang_thai === 1 ? 'badge badge-success' : (b.trang_thai === 2 ? 'badge badge-warning' : 'badge badge-error')">{{ {1: 'Trống', 2: 'Đang dùng', 0: 'Bảo trì'}[b.trang_thai] || 'Bảo trì' }}</span></td>
          <td class="action-cell">
            <button class="btn btn-ghost btn-sm" @click="openQr(b)" title="Xem mã QR">QR</button>
            <button class="btn btn-ghost btn-sm" @click="openBan(b)">Sửa</button>
            <button class="btn btn-danger btn-sm" @click="delBan(b)">Xóa</button>
          </td>
        </tr></tbody>
      </table>
    </div>
    <Teleport to="body">
    <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
      <div class="modal-box card">
        <h3>{{ modalTitle }}</h3>
        <div class="modal-body">
          <div class="modal-form">
            <div v-for="f in fields" :key="f.key" class="form-group">
              <label>{{ f.label }}</label>
              <select v-if="f.type==='select'" v-model="form[f.key]"><option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option></select>
              <input v-else v-model="form[f.key]" :disabled="f.disabled" />
            </div>
          </div>
        </div>
        <div class="modal-actions"><button class="btn btn-ghost" @click="showModal=false">Hủy</button><button class="btn btn-primary" @click="saveModal">Lưu</button></div>
      </div>
    </div>

    <!-- Modal QR Code -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="showQrModal=false">
      <div class="modal-box card qr-modal">
        <h3>Mã QR Bàn: {{ selectedBan?.ten_ban }}</h3>
        <div class="modal-body qr-content" id="printable-qr">
          <div class="qr-info">
            <div class="brand">GUNPLA COFFE</div>
            <div class="table-name">{{ selectedBan?.ten_ban }}</div>
            <div class="area-name">{{ selectedBan?.ten_khu_vuc }}</div>
          </div>
          <div class="qr-wrapper">
            <qrcode-vue :value="qrValue" :size="200" level="H" />
          </div>
          <div class="qr-footer">Quét mã để gọi món tại bàn</div>
        </div>
        <div class="form-group qr-config no-print">
          <label>Link Web Order (Base URL):</label>
          <input v-model="webOrderBaseUrl" placeholder="VD: http://192.168.1.5:5173" />
          <small>Thay đổi IP để test trên điện thoại (LAN)</small>
        </div>
        <div class="modal-actions no-print">
          <button class="btn btn-ghost" @click="showQrModal=false">Đóng</button>
          <button class="btn btn-primary" @click="printQr">In mã QR</button>
        </div>
      </div>
    </div>
    </Teleport>
  </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue'
import QrcodeVue from 'qrcode.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
const toast = useToast()
const { confirm } = useConfirm()
const tab = ref('kv'), khuVucs = ref([]), bans = ref([]), banFilter = ref('')
const showModal = ref(false), modalTitle = ref(''), fields = ref([]), form = ref({}), err = ref('')
const showQrModal = ref(false), selectedBan = ref(null), webOrderBaseUrl = ref('http://localhost:5174')
const qrValue = computed(() => {
  if (!selectedBan.value) return ''
  const baseUrl = webOrderBaseUrl.value.replace(/\/$/, '')
  return `${baseUrl}/?table=${selectedBan.value.ma_ban}`
})
let saveFn = null
async function loadKV() { try { const r = await api.getKhuVuc(); khuVucs.value = r.data.data } catch(e){} }
async function loadBans() { try { const r = await api.getBan({ khu_vuc_id: banFilter.value }); bans.value = r.data.data } catch(e){} }
function openKV(k) {
  modalTitle.value = k ? 'Sửa khu vực' : 'Thêm khu vực'
  form.value = k ? { ma_khu_vuc: k.ma_khu_vuc, ten_khu_vuc: k.ten_khu_vuc } : { ma_khu_vuc: '', ten_khu_vuc: '' }
  fields.value = [{ key:'ma_khu_vuc', label:'Mã', disabled:!!k },{ key:'ten_khu_vuc', label:'Tên khu vực' }]
  err.value = ''; saveFn = async () => { if(k) await api.updateKhuVuc(k.id, form.value); else await api.createKhuVuc(form.value); await loadKV(); toast.success(k ? 'Cập nhật thành công!' : 'Thêm khu vực thành công!') }
  showModal.value = true
}
async function delKV(k) {
  const ok = await confirm(`Xóa khu vực "${k.ten_khu_vuc}"?`, 'Xóa khu vực')
  if (!ok) return
  try { await api.deleteKhuVuc(k.id); toast.success('Đã xóa!'); await loadKV() } catch(e) { toast.error(e.response?.data?.message||'Lỗi') }
}
function openBan(b) {
  modalTitle.value = b ? 'Sửa bàn' : 'Thêm bàn'
  form.value = b ? { ...b } : { ma_ban:'', ten_ban:'', khu_vuc_id:'', ma_qr:'', trang_thai: 1 }
  fields.value = [
    { key:'ma_ban', label:'Mã bàn', disabled:!!b },{ key:'ten_ban', label:'Tên bàn' },
    { key:'khu_vuc_id', label:'Khu vực', type:'select', options: khuVucs.value.map(k=>({value:k.id,label:k.ten_khu_vuc})) },
    { key:'ma_qr', label:'Mã QR' },{ key:'trang_thai', label:'Trạng thái', type:'select', options:[{value: 1,label:'Trống'},{value: 2,label:'Đang dùng'},{value: 0,label:'Bảo trì'}] },
  ]
  err.value = ''; saveFn = async () => { if(b) await api.updateBan(b.id, form.value); else await api.createBan(form.value); await loadBans(); toast.success(b ? 'Cập nhật thành công!' : 'Thêm bàn thành công!') }
  showModal.value = true
}
async function delBan(b) {
  const ok = await confirm(`Xóa bàn "${b.ten_ban}"?`, 'Xóa bàn')
  if (!ok) return
  try { await api.deleteBan(b.id); toast.success('Đã xóa!'); await loadBans() } catch(e) { toast.error(e.response?.data?.message||'Lỗi') }
}
function openQr(b) {
  selectedBan.value = b
  showQrModal.value = true
}
function printQr() {
  window.print()
}
async function saveModal() { try { await saveFn(); showModal.value=false } catch(e){ toast.error(e.response?.data?.message || 'Không thể lưu thông tin. Vui lòng kiểm tra lại dữ liệu.') } }
onMounted(() => { loadKV(); loadBans() })
</script>
<style scoped>
.tabs { display:flex;gap:4px;margin-bottom:24px;background:var(--bg-secondary);border-radius:var(--radius-lg);padding:4px;width:fit-content }
.tab-btn { padding:10px 24px;border:none;background:none;color:var(--text-secondary);font-weight:500;border-radius:var(--radius-md);cursor:pointer;transition:all var(--transition-fast) }
.tab-btn.active { background:var(--accent);color:#FFFFFF;font-weight:600 }
.toolbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px }
.toolbar-actions { display:flex;gap:10px }
.filter-select { width:180px;padding:8px 12px }
.action-cell { display:flex;gap:6px }
.qr-modal { max-width: 400px; text-align: center; }
.qr-content { padding: 20px; background: white; border: 1px solid #eee; border-radius: 12px; margin-bottom: 20px; }
.qr-info { margin-bottom: 15px; }
.qr-info .brand { font-weight: 800; font-size: 1.5rem; color: #6E4423; }
.qr-info .table-name { font-size: 1.2rem; font-weight: 600; margin-top: 5px; }
.qr-info .area-name { color: #888; font-size: 0.9rem; }
.qr-wrapper { display: flex; justify-content: center; padding: 15px; background: #fff; }
.qr-footer { margin-top: 15px; font-style: italic; color: #666; font-size: 0.85rem; }
.qr-config { text-align: left; margin-top: 10px; padding: 0 10px; }
.qr-config input { margin-top: 5px; width: 100%; }
.qr-config small { display: block; margin-top: 4px; color: #999; }

@media print {
  body * { visibility: hidden; }
  #printable-qr, #printable-qr * { visibility: visible; }
  #printable-qr { position: absolute; left: 0; top: 0; width: 100%; text-align: center; padding: 40px 0; border: none; }
  .no-print { display: none !important; }
}
</style>
