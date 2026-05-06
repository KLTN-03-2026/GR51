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
  confirm: (msg) => toastRef.value?.showConfirm(msg),
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
      toast.error("Không đủ nguyên liệu để chế biến món này.")
    } else if (error.response?.status === 403) {
      toast.error(error.response.data.message || "Cửa hàng hiện đang tạm đóng.")
    } else {
      toast.error("Lỗi khi đặt hàng. Vui lòng thử lại sau!")
    }
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
  </div>
</template>

<style scoped>
.app-container { display: flex; flex-direction: column; min-height: 100vh; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(5px); }
</style>
