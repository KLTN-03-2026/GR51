<script setup>
import { inject, ref, onMounted, onUnmounted } from 'vue'
import api from '../services/api'

// We receive `placedOrders` from App.vue
const placedOrders = inject('placedOrders')
const emit = defineEmits(['back'])

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(price)).replace('₫', 'đ')
}

// Status Display Mapping
const statusConfig = {
  'cho_xac_nhan': { label: 'Chờ xác nhận', color: '#FCD34D', icon: '⏳' },
  'dang_pha': { label: 'Đang pha chế', color: '#F97316', icon: '☕' },
  // hoan_thanh will be handled dynamically based on payment status
  'huy': { label: 'Đã Huỷ', color: '#EF4444', icon: '❌' }
}

const getStatusProps = (order) => {
  // SỬA Ở ĐÂY: Đổi 'hoan_thanh' thành 'hoan_thanh_pha_che' cho khớp với Database
  if (order.trang_thai_don === 'hoan_thanh_pha_che' || order.trang_thai_don === 'hoan_thanh') {
    
    // Nếu đã thanh toán
    if (order.trang_thai_thanh_toan === 'da_thanh_toan') {
      return { 
        label: order.da_danh_gia ? 'Hoàn tất! Xin cảm ơn' : 'Cảm ơn quý khách đã sử dụng dịch vụ của quán! Xin để lại đánh giá', 
        color: '#3B82F6', 
        icon: '✅' 
      }
    } 
    // Nếu chưa thanh toán
    else {
      return { 
        label: 'Đã hoàn thành đơn hàng! Vui lòng thanh toán sau khi nhân viên mang nước ra.', 
        color: '#10B981', 
        icon: '🎉' 
      }
    }
  }
  
  // Các trạng thái khác (chờ xác nhận, đang pha chế...)
  return statusConfig[order.trang_thai_don] || { label: order.trang_thai_don, color: '#9CA3AF', icon: '📌' }
}

const reviewState = ref({})

const setRating = (maDonHang, stars) => {
  if (!reviewState.value[maDonHang]) {
    reviewState.value[maDonHang] = { rating: stars, text: '', submitting: false }
  } else {
    reviewState.value[maDonHang].rating = stars
  }
}

const submitReview = async (order) => {
  const rs = reviewState.value[order.ma_don_hang]
  if (!rs || !rs.rating) {
    alert("Vui lòng chọn số sao.")
    return
  }

  rs.submitting = true
  try {
    const payload = {
      ma_don_hang: order.ma_don_hang,
      so_sao: rs.rating,
      binh_luan: rs.text || ''
    }
    const res = await api.submitReview(payload)
    if (res.data && res.data.success) {
      order.da_danh_gia = true
    }
  } catch (error) {
    alert("Lỗi khi gửi đánh giá. Vui lòng thử lại.")
    console.error(error)
  } finally {
    rs.submitting = false
  }
}

let pollingInterval = null

onMounted(() => {
  // Start polling every 10 seconds for orders that are not finished
  pollingInterval = setInterval(async () => {
    // Only poll orders that aren't completed or cancelled
    const activeOrders = placedOrders.value.filter(o => 
      o.trang_thai_don !== 'huy' && 
      !(o.trang_thai_don === 'hoan_thanh' && o.trang_thai_thanh_toan === 'da_thanh_toan' && o.da_danh_gia !== undefined)
    );
    
    for (let order of activeOrders) {
      try {
        const res = await api.getOrderStatus(order.ma_don_hang);
        if (res.data && res.data.success) {
          const latestData = res.data.data;
          // Cập nhật trạng thái
          order.trang_thai_don = latestData.trang_thai_don;
          order.trang_thai_thanh_toan = latestData.trang_thai_thanh_toan;
          order.da_danh_gia = latestData.da_danh_gia;
        }
      } catch (error) {
        console.error("Lỗi cập nhật trạng thái đơn", error);
      }
    }
  }, 10000);
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
})
</script>

<template>
  <div class="cart-view animate-fade-in">
    <header class="cart-header">
      <h2>Trạng thái Đơn hàng</h2>
    </header>

    <div v-if="placedOrders.length === 0" class="empty-state">
      <div class="empty-icon">☕</div>
      <p>Bạn chưa có đơn hàng nào.</p>
      <button class="primary-btn mt-6" @click="emit('back')">Mở thực đơn</button>
    </div>

    <div v-else class="orders-list">
      <div v-for="order in placedOrders" :key="order.ma_don_hang" class="order-card">
        <!-- Order Header -->
        <div class="order-header">
          <span class="order-id">Mã đơn: <strong>{{ order.ma_don_hang }}</strong></span>
          <span class="order-total">{{ formatPrice(order.tong_tien) }}</span>
        </div>

        <!-- Order Status -->
        <div class="order-status-badge" :style="{ backgroundColor: getStatusProps(order).color + '20', color: getStatusProps(order).color }">
          <span class="status-icon">{{ getStatusProps(order).icon }}</span>
          <span class="status-text">{{ getStatusProps(order).label }}</span>
        </div>

        <!-- Review Form -->
        <div v-if="order.trang_thai_don === 'hoan_thanh' && order.trang_thai_thanh_toan === 'da_thanh_toan' && !order.da_danh_gia" class="review-box">
          <p class="review-prompt">Bạn cảm thấy đồ uống hôm nay thế nào?</p>
          <div class="stars">
            <span v-for="i in 5" :key="i" class="star" 
                  :class="{ active: reviewState[order.ma_don_hang]?.rating >= i }"
                  @click="setRating(order.ma_don_hang, i)">
              ☕
            </span>
          </div>
          
          <div v-if="reviewState[order.ma_don_hang]?.rating" class="review-form-expand animate-fade-in">
            <textarea 
              v-model="reviewState[order.ma_don_hang].text" 
              placeholder="Chia sẻ thêm trải nghiệm của bạn (không bắt buộc)..." 
              class="review-input" 
              rows="2">
            </textarea>
            <button class="primary-btn btn-sm mt-3" 
                    @click="submitReview(order)" 
                    :disabled="reviewState[order.ma_don_hang].submitting">
              {{ reviewState[order.ma_don_hang].submitting ? 'Đang gửi...' : 'Gửi Đánh Giá' }}
            </button>
          </div>
        </div>
        
        <!-- Review Success -->
        <div v-if="order.da_danh_gia" class="review-success">
          ⭐ Cảm ơn bạn đã để lại đánh giá!
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
          <div class="payment-status">
            <span>Thanh toán:</span>
            <span v-if="order.trang_thai_thanh_toan === 'da_thanh_toan'" class="paid-badge">✅ Đã thanh toán</span>
            <span v-else class="unpaid-badge">⚠️ Chưa thanh toán</span>
          </div>
          <div class="payment-method">
            {{ order.phuong_thuc_thanh_toan === 'tien_mat' ? '💵 Tiền mặt (Khi nhận)' : '🏦 Chuyển khoản (VietQR)' }}
          </div>
        </div>

        <!-- VietQR Box (Only show if bank transfer and unpaid) -->
        <div v-if="order.phuong_thuc_thanh_toan === 'chuyen_khoan' && order.trang_thai_thanh_toan !== 'da_thanh_toan'" class="vietqr-box">
          <div class="qr-title">Quét mã để thanh toán ngay</div>
           <img :src="'https://api.vietqr.io/image/970436-123456789-F2lZkQK.jpg?amount=' + order.tong_tien + '&addInfo=ThanhToan' + order.ma_don_hang" alt="VietQR" class="qr-image" />
           <p class="qr-desc">Thu ngân sẽ chuẩn bị món ngay sau khi nhận được tiền.</p>
        </div>
      </div>

      <button class="outline-btn mt-6" @click="emit('back')">Đặt thêm món mới</button>
    </div>
  </div>
</template>

<style scoped>
.cart-view {
  min-height: 100vh;
  background-color: var(--color-bg);
  padding: 24px;
  padding-bottom: 100px;
}

.cart-header {
  margin-bottom: 24px;
  text-align: center;
}

.cart-header h2 {
  font-size: 22px;
  color: var(--color-text-brown);
  margin: 0;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  background-color: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state p {
  color: var(--color-text-light);
  font-size: 16px;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-card {
  background-color: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  border: 1px solid rgba(0,0,0,0.03);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px dashed var(--color-border);
}

.order-id {
  font-size: 14px;
  color: var(--color-text-light);
}

.order-id strong {
  color: var(--color-text-main);
  font-family: monospace;
  font-size: 16px;
}

.order-total {
  font-size: 18px;
  font-weight: bold;
  color: var(--color-primary);
}

.order-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  width: 100%;
  margin-bottom: 16px;
}

.status-icon {
  font-size: 20px;
}

.payment-info {
  background-color: #F8F9FA;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 16px;
}

.payment-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  font-size: 14px;
  color: var(--color-text-main);
}

.paid-badge { color: #10B981; font-weight: bold; }
.unpaid-badge { color: #F59E0B; font-weight: bold; }

.payment-method {
  font-size: 14px;
  color: var(--color-text-light);
  font-weight: 500;
}

.vietqr-box {
  background-color: white;
  border: 2px solid var(--color-primary);
  border-radius: 12px;
  padding: 16px;
  text-align: center;
}

.qr-title {
  font-size: 15px;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 12px;
}

.qr-image {
  width: 200px;
  height: 200px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  margin: 0 auto;
}

.qr-desc {
  font-size: 13px;
  color: var(--color-text-light);
  margin-top: 12px;
}

.primary-btn {
  width: 100%;
  padding: 16px;
  background-color: var(--color-primary);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.primary-btn:active {
  transform: scale(0.98);
}

.outline-btn {
  width: 100%;
  padding: 16px;
  background-color: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.outline-btn:active {
  background-color: var(--color-primary);
  color: white;
}

.review-box {
  background-color: #FDF8F3;
  border: 1px dashed var(--color-primary);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  text-align: center;
}

.review-prompt {
  font-size: 15px;
  color: var(--color-text-main);
  font-weight: 600;
  margin-bottom: 12px;
}

.stars {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
  direction: ltr;
}

.star {
  font-size: 32px;
  color: #E5E7EB;
  cursor: pointer;
  transition: transform 0.1s, color 0.2s;
}

.star:active {
  transform: scale(0.9);
}

.star.active {
  color: var(--color-primary);
}

.review-form-expand {
  margin-top: 12px;
}

.review-input {
  width: 100%;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 12px;
  font-family: inherit;
  font-size: 14px;
  resize: none;
  background-color: white;
}

.review-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.btn-sm {
  padding: 10px;
  font-size: 14px;
}

.review-success {
  background-color: #ECFDF5;
  color: #059669;
  padding: 12px;
  border-radius: 12px;
  text-align: center;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 16px;
}

.mt-3 { margin-top: 12px; }
.mt-6 { margin-top: 24px; }
.mb-6 { margin-bottom: 24px; }
</style>
