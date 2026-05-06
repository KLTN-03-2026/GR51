<template>
  <div class="staff-page fade-in">
    <div class="toolbar">
      <div class="toolbar-actions">
        <input v-model="search" placeholder="Tìm nhân sự..." class="search-input" @input="load" />
        <select v-model="roleFilter" class="filter-select" @change="load">
          <option value="">Tất cả vai trò</option><option value="quan_ly">Quản lý</option><option value="nhan_vien">Nhân viên</option>
        </select>
      </div>
      <button class="btn btn-primary" @click="openForm()">+ Thêm nhân sự</button>
    </div>
    <table class="data-table">
      <thead><tr><th>Mã</th><th>Họ tên</th><th>Tên đăng nhập</th><th>SĐT</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody><tr v-for="ns in items" :key="ns.id">
        <td style="color:var(--text-primary)">{{ ns.ma_nhan_su }}</td>
        <td style="color:var(--text-primary);font-weight:500">{{ ns.ho_ten }}</td>
        <td>{{ ns.ten_dang_nhap }}</td><td>{{ ns.so_dien_thoai }}</td>
        <td><span :class="ns.vai_tro==='quan_ly'?'badge badge-info':'badge badge-success'">{{ ns.vai_tro==='quan_ly'?'Quản lý':'Nhân viên' }}</span></td>
        <td><span :class="ns.trang_thai === 1 ? 'badge badge-success' : 'badge badge-error'">{{ ns.trang_thai === 1 ? 'Hoạt động' : 'Khóa' }}</span></td>
        <td class="action-cell">
          <button class="btn btn-ghost btn-sm" @click="openForm(ns)">Sửa</button>
          <button class="btn btn-secondary btn-sm" @click="openReset(ns)">🔑</button>
          <button class="btn btn-danger btn-sm" @click="del(ns)">Xóa</button>
        </td>
      </tr></tbody>
    </table>

    <!-- Form Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
        <div class="modal-box card">
          <h3>{{ editing ? 'Sửa nhân sự' : 'Thêm nhân sự' }}</h3>
          <div class="modal-body">
            <div class="modal-form">
              <div class="form-group"><label>Mã nhân sự</label><input v-model="form.ma_nhan_su" :disabled="editing" /></div>
              <div class="form-group"><label>Họ tên</label><input v-model="form.ho_ten" /></div>
              <div class="form-group"><label>Tên đăng nhập</label><input v-model="form.ten_dang_nhap" :disabled="editing" /></div>
              <div class="form-group"><label>SĐT</label><input v-model="form.so_dien_thoai" /></div>
              <div class="form-group"><label>Vai trò</label><select v-model="form.vai_tro"><option value="nhan_vien">Nhân viên</option><option value="quan_ly">Quản lý</option></select></div>
              <div class="form-group"><label>Trạng thái</label><select v-model="form.trang_thai"><option :value="1">Hoạt động</option><option :value="0">Khóa</option></select></div>
              <template v-if="!editing">
                <div class="form-group"><label>Mật khẩu</label><input v-model="form.mat_khau" type="password" /></div>
                <div class="form-group"><label>Mã PIN (4-6 số)</label><input v-model="form.ma_pin" /></div>
              </template>
            </div>
          </div>
          <div v-if="formErr" class="error-msg">{{ formErr }}</div>
          <div class="modal-actions">
            <button class="btn btn-ghost" @click="showModal=false">Hủy</button>
            <button class="btn btn-primary" @click="save" :disabled="saving">{{ saving ? 'Đang lưu...' : 'Lưu' }}</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Reset PW Modal -->
    <Teleport to="body">
      <div v-if="showReset" class="modal-overlay" @click.self="showReset=false">
        <div class="modal-box card" style="max-width:400px">
          <h3>🔑 Đặt lại mật khẩu</h3>
          <p style="color:var(--text-secondary);margin-bottom:16px">Nhân viên: <strong style="color:var(--text-primary)">{{ resetUser?.ho_ten }}</strong></p>
          <div class="modal-form"><div class="form-group"><label>Mật khẩu mới</label><input v-model="newPw" type="password" /></div></div>
          <div class="modal-actions"><button class="btn btn-ghost" @click="showReset=false">Hủy</button><button class="btn btn-primary" @click="resetPw">Đặt lại</button></div>
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

const items = ref([]), search = ref(''), roleFilter = ref('')
const showModal = ref(false), editing = ref(false), form = ref({}), formErr = ref(''), saving = ref(false)
const showReset = ref(false), resetUser = ref(null), newPw = ref('')

async function load() {
  try { const r = await api.getNhanSu({ search: search.value, vai_tro: roleFilter.value }); items.value = r.data.data } catch(e) { toast.error('Lỗi tải danh sách nhân sự') }
}

function openForm(ns) {
  editing.value = !!ns; formErr.value = ''; saving.value = false
  form.value = ns ? { ...ns } : { ma_nhan_su:'', ten_dang_nhap:'', ho_ten:'', so_dien_thoai:'', vai_tro:'nhan_vien', trang_thai: 1, mat_khau:'', ma_pin:'' }
  showModal.value = true
}

async function save() {
  saving.value = true; formErr.value = ''
  try {
    if (editing.value) await api.updateNhanSu(form.value.id, form.value)
    else await api.createNhanSu(form.value)
    showModal.value = false
    toast.success(editing.value ? 'Cập nhật nhân sự thành công!' : 'Thêm nhân sự thành công!')
    await load()
  } catch(e) {
    formErr.value = e.response?.data?.message || 'Lỗi khi lưu'
  } finally {
    saving.value = false
  }
}

async function del(ns) {
  const ok = await confirm(`Bạn có chắc muốn xóa nhân sự "${ns.ho_ten}"?`, 'Xóa nhân sự')
  if (!ok) return
  try { await api.deleteNhanSu(ns.id); toast.success('Đã xóa nhân sự!'); await load() } catch(e) { toast.error(e.response?.data?.message || 'Lỗi khi xóa') }
}

function openReset(ns) { resetUser.value = ns; newPw.value = ''; showReset.value = true }

async function resetPw() {
  if (!newPw.value) { toast.warning('Vui lòng nhập mật khẩu mới'); return }
  try {
    await api.resetPassword(resetUser.value.id, { mat_khau_moi: newPw.value })
    showReset.value = false
    toast.success('Đặt lại mật khẩu thành công!')
  } catch(e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

onMounted(load)
</script>

<style scoped>
.toolbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px }
.toolbar-actions { display:flex;gap:10px }
.search-input { width:200px;padding:8px 12px }
.filter-select { width:160px;padding:8px 12px }
.action-cell { display:flex;gap:6px }
</style>
