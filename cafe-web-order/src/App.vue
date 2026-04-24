<script setup>
import { ref, onMounted, provide } from 'vue'
import api from './services/api'
import MenuView from './views/MenuView.vue'
import CartView from './views/CartView.vue'
import BottomNav from './components/layout/BottomNav.vue'

const currentTab = ref('menu')
const tableCode = ref(null)
const tableName = ref(null)

// Cart state
const cart = ref([]) // Giỏ hàng nháp (Slide Drawer)
const placedOrders = ref([]) // Lịch sử đơn hàng

onMounted(async () => {
  // Parse table code from URL
  const urlParams = new URLSearchParams(window.location.search)
  const table = urlParams.get('table')
  if (table) {
    tableCode.value = table
    try {
      const res = await api.getTableInfo(table)
      if (res.data && res.data.success) {
        tableName.value = res.data.data.ten_ban
      }
    } catch(e) {
      console.log('Không lấy được tên bàn', e)
    }
  }
})

// Provide state to children
provide('cart', cart)
provide('placedOrders', placedOrders)
provide('tableCode', tableCode)
provide('tableName', tableName)

const switchTab = (tab) => {
  currentTab.value = tab
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleCheckout = async (paymentMethod) => {
  if (cart.value.length === 0) return;

  try {
    const payload = {
      ma_ban: tableCode.value || null,
      loai_don: tableCode.value ? 'tai_ban' : 'mang_di',
      phuong_thuc_thanh_toan: paymentMethod,
      trang_thai_thanh_toan: 'chua_thanh_toan',
      chi_tiets: cart.value.map(item => ({
        ma_mon: item.ma_mon,
        so_luong: item.quantity,
        don_gia: item.gia_ban,
        ghi_chu: item.ghi_chu || ''
      }))
    }

    const res = await api.submitQrOrder(payload)
    if (res.data && res.data.success) {
      placedOrders.value.unshift(res.data.data) // Đẩy lên đầu danh sách
      cart.value.splice(0, cart.value.length) // Xoá giỏ nháp
      switchTab('cart') // Mở trang trạng thái đơn hàng
    }
  } catch (error) {
    console.error("Lỗi đặt hàng", error)
    alert("Đã có lỗi xảy ra khi đặt hàng. Vui lòng thử lại sau!");
  }
}
</script>

<template>
  <div class="app-container">
    <!-- Active View -->
    <Transition name="fade" mode="out-in">
      <MenuView v-if="currentTab === 'menu'" @submit-order="handleCheckout" />
      <CartView v-else-if="currentTab === 'cart'" @back="switchTab('menu')" />
    </Transition>

    <!-- Navigation -->
    <BottomNav :current-tab="currentTab" @change-tab="switchTab" :cart-count="placedOrders.length" />
  </div>
</template>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(5px);
}
</style>
