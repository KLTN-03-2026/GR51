<script setup>
import { inject, ref, onMounted, onUnmounted } from 'vue'
import api from '../services/api'

const placedOrders = inject('placedOrders')
const toast = inject('toast')
const emit = defineEmits(['back'])

const formatPrice = (price) => new Intl.NumberFormat('vi-VN').format(price) + 'đ'

// Status Mapping (0: Chờ, 1: Đang pha, 2: Hoàn thành, 3: Đã hủy)
const statusConfig = {
  0: { label: 'Đang pha chế', color: '#3B82F6', icon: '☕' },
  1: { label: 'Đang pha chế', color: '#F97316', icon: '☕' },
  3: { label: 'Đã Huỷ', color: '#EF4444', icon: '❌' }
}

const getStatusProps = (order) => {
  if (order.trang_thai_don === 2) {
    if (order.trang_thai_thanh_toan === 1) {
      return { label: order.da_danh_gia ? 'Hoàn tất! Xin cảm ơn' : 'Cảm ơn! Hãy để lại đánh giá nhé', color: '#3B82F6', icon: '✅' }
    } else {
      return { label: 'Đã xong! Vui lòng đợi nhân viên mang nước ra và thanh toán.', color: '#10B981', icon: '🎉' }
    }
  }
  return statusConfig[order.trang_thai_don] || { label: 'Đang xử lý', color: '#9CA3AF', icon: '📌' }
}

const reviewState = ref({})
const setRating = (id, stars) => {
  if (!reviewState.value[id]) reviewState.value[id] = { rating: stars, text: '', submitting: false }
  else reviewState.value[id].rating = stars
}

const submitReview = async (order) => {
  const rs = reviewState.value[order.id]
  if (!rs || !rs.rating) { toast.info("Vui lòng chọn số sao."); return }
  rs.submitting = true
  try {
    await api.submitReview({ don_hang_id: order.id, so_sao: rs.rating, binh_luan: rs.text || '' })
    order.da_danh_gia = true
    toast.success("Cảm ơn bạn đã đánh giá!")
  } catch (error) {
    toast.error("Lỗi khi gửi đánh giá.")
  } finally { rs.submitting = false }
}

const cancellingOrder = ref(null)
const canCancelOrder = (order) => {
  if (order.trang_thai_don !== 0) return false
  const createdAt = new Date(order.created_at_local || order.created_at)
  return (new Date() - createdAt) <= 2 * 60 * 1000
}

const cancelOrder = async (order) => {
  if (!await toast.confirm('Bạn muốn huỷ đơn này?')) return
  cancellingOrder.value = order.id
  try {
    const res = await api.cancelOrderQr(order.id)
    if (res.data.success) { order.trang_thai_don = 3; toast.success('Đã huỷ đơn!') }
  } catch (error) { toast.error("Không thể huỷ đơn lúc này.") }
  finally { cancellingOrder.value = null }
}

let pollingInterval = null
onMounted(() => {
  pollingInterval = setInterval(async () => {
    // Poll if: not cancelled (3) AND (not completed (2) OR not paid (1))
    const activeOrders = placedOrders.value.filter(o => 
      o.trang_thai_don < 2 || (o.trang_thai_don === 2 && o.trang_thai_thanh_toan === 0)
    )
    for (let order of activeOrders) {
      try {
        const res = await api.getOrderStatus(order.id || order.ma_don_hang)
        if (res.data.success) {
          const d = res.data.data
          order.trang_thai_don = d.trang_thai_don
          order.trang_thai_thanh_toan = d.trang_thai_thanh_toan
          order.da_danh_gia = d.da_danh_gia || !!d.danh_gia
        }
      } catch (e) {}
    }
  }, 5000)
})
onUnmounted(() => { if (pollingInterval) clearInterval(pollingInterval) })
</script>

<template>
  <div class="cart-view animate-fade-in">
    <header class="cart-header"><h2>Đơn hàng của bạn</h2></header>
    <div v-if="placedOrders.length === 0" class="empty-state">
      <div class="empty-icon">☕</div><p>Bạn chưa có đơn hàng nào.</p>
      <button class="primary-btn mt-6" @click="emit('back')">Quay lại thực đơn</button>
    </div>
    <div v-else class="orders-list">
      <div v-for="order in placedOrders" :key="order.id || order.ma_don_hang" class="order-card">
        <div class="order-header">
          <span class="order-id">Mã đơn: <strong>{{ (order.ma_don_hang || '').slice(-8) }}</strong></span>
          <span class="order-total">{{ formatPrice(order.tong_tien) }}</span>
        </div>
        <div class="order-status-badge" :style="{ backgroundColor: getStatusProps(order).color + '15', color: getStatusProps(order).color }">
          <span class="status-icon">{{ getStatusProps(order).icon }}</span>
          <span class="status-text">{{ getStatusProps(order).label }}</span>
        </div>

        <div v-if="order.trang_thai_don === 2 && order.trang_thai_thanh_toan === 1 && !order.da_danh_gia" class="review-box">
          <p class="review-prompt">Bạn thấy đồ uống thế nào?</p>
          <div class="stars">
            <span v-for="i in 5" :key="i" class="star" :class="{ active: reviewState[order.id]?.rating >= i }" @click="setRating(order.id, i)">☕</span>
          </div>
          <div v-if="reviewState[order.id]?.rating" class="review-form-expand animate-fade-in">
            <textarea v-model="reviewState[order.id].text" placeholder="Góp ý cho quán..." class="review-input" rows="2"></textarea>
            <button class="primary-btn btn-sm mt-3" @click="submitReview(order)" :disabled="reviewState[order.id].submitting">Gửi đánh giá</button>
          </div>
        </div>

        <div class="payment-info">
          <div class="payment-status">
            <span>Thanh toán:</span>
            <span v-if="order.trang_thai_thanh_toan === 1" class="paid-badge">✅ Đã thanh toán</span>
            <span v-else class="unpaid-badge">⚠️ Chưa thanh toán</span>
          </div>
        </div>

        <div v-if="order.phuong_thuc_thanh_toan === 'chuyen_khoan' && order.trang_thai_thanh_toan !== 1" class="vietqr-box">
           <img :src="'https://api.vietqr.io/image/970436-123456789-F2lZkQK.jpg?amount=' + order.tong_tien + '&addInfo=CK' + (order.ma_don_hang || '').slice(-8)" alt="VietQR" class="qr-image" />
           <p class="qr-desc">Vui lòng quét mã để thanh toán.</p>
        </div>

        <div v-if="canCancelOrder(order)" class="cancel-section">
          <button class="cancel-btn" @click="cancelOrder(order)" :disabled="cancellingOrder === order.id">Huỷ đơn hàng</button>
        </div>
      </div>
      <button class="outline-btn mt-6" @click="emit('back')">Tiếp tục đặt món</button>
    </div>
  </div>
</template>

<style scoped>
.cart-view { min-height: 100vh; background: #fafafa; padding: 24px; padding-bottom: 100px; }
.cart-header { margin-bottom: 24px; text-align: center; }
.order-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
.order-id { font-size: 14px; color: #888; }
.order-total { font-weight: 700; color: var(--color-primary); font-size: 18px; }
.order-status-badge { display: flex; align-items: center; gap: 8px; padding: 12px; border-radius: 12px; font-weight: 700; margin-bottom: 15px; }
.payment-info { background: #f9f9f9; padding: 12px; border-radius: 12px; margin-bottom: 15px; font-size: 14px; }
.paid-badge { color: #10B981; } .unpaid-badge { color: #F59E0B; }
.vietqr-box { text-align: center; border: 1px solid #eee; padding: 15px; border-radius: 12px; }
.qr-image { width: 180px; height: 180px; margin: 0 auto; }
.primary-btn { width: 100%; background: var(--color-primary); color: #fff; padding: 14px; border-radius: 12px; font-weight: 600; }
.outline-btn { width: 100%; border: 2px solid var(--color-primary); color: var(--color-primary); padding: 14px; border-radius: 12px; font-weight: 600; }
.stars { display: flex; justify-content: center; gap: 8px; margin: 10px 0; font-size: 24px; }
.star.active { color: var(--color-primary); }
.cancel-btn { width: 100%; border: 1px solid #ff4d4f; color: #ff4d4f; padding: 10px; border-radius: 10px; background: #fff; margin-top: 10px; }
</style>
