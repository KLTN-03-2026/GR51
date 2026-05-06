<template>
  <Teleport to="body">
    <!-- Toast Messages -->
    <TransitionGroup name="toast" tag="div" class="toast-container">
      <div v-for="t in toasts" :key="t.id" class="toast-item" :class="'toast-' + t.type">
        <span class="toast-icon">{{ t.type === 'success' ? '✅' : t.type === 'error' ? '❌' : 'ℹ️' }}</span>
        <span class="toast-msg">{{ t.message }}</span>
      </div>
    </TransitionGroup>

    <!-- Confirm Dialog -->
    <Transition name="fade">
      <div v-if="confirmData" class="confirm-overlay" @click.self="resolveConfirm(false)">
        <div class="confirm-box">
          <div class="confirm-icon">⚠️</div>
          <p class="confirm-msg">{{ confirmData.message }}</p>
          <div class="confirm-actions">
            <button class="confirm-btn cancel" @click="resolveConfirm(false)">Quay lại</button>
            <button class="confirm-btn ok" @click="resolveConfirm(true)">Xác nhận</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const toasts = ref([])
const confirmData = ref(null)
let confirmResolver = null

let toastId = 0

const showToast = (message, type = 'info', duration = 3000) => {
  const id = ++toastId
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, duration)
}

const showConfirm = (message) => {
  return new Promise((resolve) => {
    confirmData.value = { message }
    confirmResolver = resolve
  })
}

const resolveConfirm = (result) => {
  confirmData.value = null
  if (confirmResolver) {
    confirmResolver(result)
    confirmResolver = null
  }
}

defineExpose({ showToast, showConfirm })
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10000;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  width: 90%;
  max-width: 400px;
  pointer-events: none;
}

.toast-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  width: 100%;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  pointer-events: auto;
  backdrop-filter: blur(10px);
}

.toast-success {
  background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
  color: #065F46;
  border: 1px solid #A7F3D0;
}

.toast-error {
  background: linear-gradient(135deg, #FEF2F2, #FECACA);
  color: #991B1B;
  border: 1px solid #FCA5A5;
}

.toast-info {
  background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
  color: #1E40AF;
  border: 1px solid #93C5FD;
}

.toast-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.toast-msg {
  flex: 1;
  line-height: 1.4;
}

/* Toast Transition */
.toast-enter-active {
  transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
}
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

/* Confirm Overlay */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 10001;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.confirm-box {
  background: white;
  border-radius: 20px;
  padding: 28px 24px 20px;
  text-align: center;
  max-width: 340px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.confirm-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.confirm-msg {
  font-size: 15px;
  color: #374151;
  line-height: 1.5;
  margin-bottom: 20px;
}

.confirm-actions {
  display: flex;
  gap: 12px;
}

.confirm-btn {
  flex: 1;
  padding: 12px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 14px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-btn.cancel {
  background: #F3F4F6;
  color: #6B7280;
}

.confirm-btn.cancel:hover {
  background: #E5E7EB;
}

.confirm-btn.ok {
  background: #EF4444;
  color: white;
}

.confirm-btn.ok:hover {
  background: #DC2626;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
