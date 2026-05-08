<script setup>
import { ref, onMounted, provide } from 'vue'
import api from './services/api'
import MenuView from './views/MenuView.vue'
import CartView from './views/CartView.vue'
import BottomNav from './components/layout/BottomNav.vue'
import ToastNotify from './components/ToastNotify.vue'

const currentTab = ref('menu')
const tableCode = ref(null)
const tableId = ref(null)
const tableName = ref(null)
const toastRef = ref(null)

// Cart state
const cart = ref([]) 
const placedOrders = ref([]) 

onMounted(async () => {
  const urlParams = new URLSearchParams(window.location.search)
  const table = urlParams.get('table')
  if (table) {
    tableCode.value = table
    try {
      const res = await api.getTableInfo(table)
      if (res.data && res.data.success) {
        tableName.value = res.data.data.ten_ban
        tableId.value = res.data.data.id
      }
    } catch(e) {
      console.log('Không lấy được tên bàn', e)
    }
  }
})

const toast = {
  success: (msg) => toastRef.value?.showToast(msg, 'success'),
  error: (msg) => toastRef.value?.showToast(msg, 'error'),
  info: (msg) => toastRef.value?.showToast(msg, 'info'),
  confirm: (msg, ok, cancel) => toastRef.value?.showConfirm(msg, ok, cancel),
}

provide('cart', cart)
provide('placedOrders', placedOrders)
provide('tableCode', tableCode)
provide('tableId', tableId)
provide('tableName', tableName)
provide('toast', toast)

const switchTab = (tab) => {
  currentTab.value = tab
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleCheckout = async (paymentMethod) => {
  if (cart.value.length === 0) return;

  const confirmed = await toast.confirm(
    "Vui lòng kiểm tra kỹ số lượng, lượng đá/topping và ghi chú.\nĐơn hàng sau khi đặt sẽ không thể tự hủy.",
    "Xác nhận đặt",
    "Xem lại"
  )

  if (!confirmed) return;

  try {
    const payload = {
      ban_id: tableId.value || null,
      ma_ban: tableCode.value || null,
      loai_don: tableId.value ? 'tai_ban' : 'mang_di',
      phuong_thuc_thanh_toan: paymentMethod,
      chi_tiets: cart.value.map(item => ({
        mon_id: item.id,
        so_luong: item.quantity,
        don_gia: item.gia_ban,
        ghi_chu: item.ghi_chu || ''
      }))
    }

    const res = await api.submitQrOrder(payload)
    if (res.data && res.data.success) {
      placedOrders.value.unshift({ ...res.data.data, created_at_local: new Date().toISOString() })
      cart.value.splice(0, cart.value.length)
      switchTab('cart')
      toast.success("Đặt món thành công! Vui lòng đợi nhân viên phục vụ.")
    }
  } catch (error) {
    console.error("Lỗi đặt hàng", error)
    if (error.response?.status === 422) {
      toast.error("Không đủ nguyên liệu để chế biến món bạn vừa order.Quý khách vui lòng giảm số lượng hoặc báo lại cho nhân viên.")
    } else if (error.response?.status === 403) {
      toast.error(error.response.data.message || "Cửa hàng hiện đang tạm đóng.")
    } else {
      toast.error("Lỗi khi đặt hàng. Vui lòng thử lại sau!")
    }
  }
}

const handleCallStaff = async () => {
  if (!tableCode.value) {
    toast.error("Vui lòng quét mã QR tại bàn để sử dụng tính năng này.")
    return
  }

  const confirmed = await toast.confirm(
    "Bạn có chắc muốn gọi nhân viên đến bàn không?",
    "Gọi ngay",
    "Hủy"
  )

  if (!confirmed) return

  try {
    const res = await api.callStaff(tableCode.value)
    if (res.data && res.data.success) {
      toast.success("Đã gửi yêu cầu! Nhân viên sẽ đến ngay.")
    }
  } catch (error) {
    console.error("Lỗi gọi nhân viên", error)
    toast.error("Không thể gửi yêu cầu gọi nhân viên.")
  }
}
</script>

<template>
  <div class="app-container">
    <ToastNotify ref="toastRef" />
    <Transition name="fade" mode="out-in">
      <MenuView v-if="currentTab === 'menu'" @submit-order="handleCheckout" />
      <CartView v-else-if="currentTab === 'cart'" @back="switchTab('menu')" />
    </Transition>
    <BottomNav :current-tab="currentTab" @change-tab="switchTab" :cart-count="placedOrders.length" />
    
    <!-- Floating Call Staff Button -->
    <button 
      class="call-staff-btn" 
      @click="handleCallStaff"
      title="Gọi nhân viên"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      <span>{{ tableCode ? 'Gọi NV' : 'Hỗ trợ' }}</span>
    </button>
  </div>
</template>

<style scoped>
.app-container { display: flex; flex-direction: column; min-height: 100vh; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(5px); }

.call-staff-btn {
  position: fixed;
  left: 20px;
  bottom: 85px; /* Above bottom nav */
  background: var(--primary-color, #c6a664);
  color: white;
  border: none;
  border-radius: 50px;
  padding: 10px 18px;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  z-index: 9999; /* Tăng cao để không bị đè */
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  pointer-events: auto; /* Đảm bảo nhận sự kiện click */
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.call-staff-btn:hover {
  transform: scale(1.05) translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

.call-staff-btn:active {
  transform: scale(0.95);
}

.call-staff-btn svg {
  animation: ring 2s infinite;
}

@keyframes ring {
  0% { transform: rotate(0); }
  5% { transform: rotate(15deg); }
  10% { transform: rotate(-15deg); }
  15% { transform: rotate(10deg); }
  20% { transform: rotate(-10deg); }
  25% { transform: rotate(0); }
  100% { transform: rotate(0); }
}
</style>
