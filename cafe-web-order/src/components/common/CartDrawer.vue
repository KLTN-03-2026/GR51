<script setup>
import { computed, ref } from 'vue'

const paymentMethod = ref('tien_mat')

const props = defineProps({
  isOpen: Boolean,
  cart: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submit-order', 'edit', 'update-quantity', 'remove'])

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(price)).replace('₫', 'đ')
}

const cartTotal = computed(() => {
  return props.cart.reduce((sum, item) => sum + (item.gia_ban * item.quantity), 0)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="slide-right">
      <div v-if="isOpen" class="drawer-overlay" @click.self="emit('close')">
        <div class="drawer-content">
          <!-- Header -->
          <div class="drawer-header">
            <div class="header-title">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <h2>Giỏ hàng <span class="badge">{{ cart.reduce((sum, item) => sum + item.quantity, 0) }}</span></h2>
            </div>
            <button class="close-btn" @click="emit('close')">
               <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          
          <!-- Body -->
          <div class="drawer-body">
            <div v-if="cart.length === 0" class="empty-cart">
              Chưa có món nào trong giỏ
            </div>
            
            <div v-else class="item-list">
              <div v-for="item in cart" :key="item.cartItemId" class="cart-item">
                <img :src="item.hinh_anh && item.hinh_anh !== 'default.jpg' ? (item.hinh_anh.startsWith('http') ? item.hinh_anh : 'http://localhost:8000/storage/' + item.hinh_anh) : 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400&h=300'" class="item-img" />
                
                <div class="item-info">
                  <div class="item-row">
                    <h3>{{ item.ten_mon }}</h3>
                    <button class="icon-btn focus-btn" @click="emit('edit', item)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                  </div>
                  
                  <p v-if="item.ghi_chu" class="item-note">{{ item.ghi_chu }}</p>
                  <p class="item-price">{{ formatPrice(item.gia_ban) }}</p>
                  
                  <div class="item-actions">
                    <button class="icon-btn trash-btn" @click="emit('remove', item)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                    <div class="qty-control">
                      <button class="qty-btn" @click="emit('update-quantity', { item, delta: -1 })">-</button>
                      <span class="qty-text">{{ item.quantity }}</span>
                      <button class="qty-btn" @click="emit('update-quantity', { item, delta: 1 })">+</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="drawer-footer">
            <div class="payment-section" v-if="cart.length > 0">
              <h3 style="margin: 0 0 12px 0; font-size: 15px; color: var(--color-text-main);">Phương thức thanh toán</h3>
              <div class="payment-methods">
                <label class="method-option" :class="{ active: paymentMethod === 'tien_mat' }">
                  <input type="radio" value="tien_mat" v-model="paymentMethod" name="payment">
                  <span>Tiền mặt (khi nhận)</span>
                </label>
                <label class="method-option" :class="{ active: paymentMethod === 'chuyen_khoan' }">
                  <input type="radio" value="chuyen_khoan" v-model="paymentMethod" name="payment">
                  <span>Chuyển khoản (quét VietQR)</span>
                </label>
              </div>
            </div>
            
            <div class="total-row">
              <span class="label">Tổng cộng</span>
              <span class="value">{{ formatPrice(cartTotal) }}</span>
            </div>
            <button class="checkout-btn" @click="emit('submit-order', paymentMethod)" :disabled="cart.length === 0">
              Đặt hàng
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.drawer-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 2000;
  display: flex;
  justify-content: flex-end; /* Slide from right */
}

.drawer-content {
  background-color: var(--color-bg);
  width: 100%;
  max-width: 400px;
  height: 100vh;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 24px rgba(0,0,0,0.1);
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--color-border);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-title h2 {
  font-size: 18px;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-brown);
}

.badge {
  background-color: var(--color-primary);
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
}

.close-btn {
  color: var(--color-text-light);
  padding: 4px;
}

.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.empty-cart {
  text-align: center;
  color: var(--color-text-light);
  margin-top: 40px;
}

.item-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cart-item {
  display: flex;
  gap: 16px;
  background-color: var(--color-white);
  padding: 12px;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
}

.item-img {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  object-fit: cover;
}

.item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 4px;
}

.item-info h3 {
  font-size: 16px;
  margin: 0;
  color: var(--color-text-main);
  line-height: 1.3;
}

.item-note {
  font-size: 13px;
  color: var(--color-text-light);
  margin-bottom: 4px;
  line-height: 1.3;
}

.item-price {
  font-weight: 600;
  color: var(--color-text-brown);
  margin-bottom: 8px;
  font-size: 15px;
}

.item-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.icon-btn {
  color: var(--color-text-light);
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.focus-btn {
  color: var(--color-primary);
}

.trash-btn {
  color: #EF4444;
  background-color: #FEF2F2;
  border-radius: 8px;
  width: 32px;
  height: 32px;
}

.qty-control {
  display: flex;
  align-items: center;
  background-color: #F8F9FA;
  border-radius: 16px;
  padding: 4px 8px;
  gap: 12px;
}

.qty-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: var(--color-text-main);
}

.qty-text {
  font-size: 15px;
  font-weight: 500;
  min-width: 16px;
  text-align: center;
}

.drawer-footer {
  padding: 20px;
  background-color: var(--color-white);
  border-top: 1px solid var(--color-border);
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 16px;
  font-family: var(--font-body);
}

.total-row .label {
  font-size: 16px;
  color: var(--color-text-main);
}

.total-row .value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-brown);
  font-family: var(--font-heading);
}

.checkout-btn {
  width: 100%;
  background-color: var(--color-primary);
  color: white;
  padding: 16px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
}

.checkout-btn:disabled {
  background-color: #CCC;
  cursor: not-allowed;
}

.payment-section {
  margin-bottom: 16px;
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.method-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.method-option.active {
  border-color: var(--color-primary);
  background-color: #FDF8F3;
}

.method-option input {
  accent-color: var(--color-primary);
  width: 18px;
  height: 18px;
  margin: 0;
}

.method-option span {
  font-size: 15px;
  color: var(--color-text-main);
  font-weight: 500;
}

/* Slide Right Animation */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: opacity 0.3s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
  opacity: 0; /* Important for the overlay itself */
}

.slide-right-enter-active .drawer-content,
.slide-right-leave-active .drawer-content {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-right-enter-from .drawer-content,
.slide-right-leave-to .drawer-content {
  transform: translateX(100%);
}
</style>
