<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div v-for="t in toast.toasts" :key="t.id" class="toast-item" :class="'toast-' + t.type" @click="toast.remove(t.id)">
          <span class="toast-icon">{{ icons[t.type] }}</span>
          <span class="toast-msg">{{ t.message }}</span>
          <button class="toast-close" @click.stop="toast.remove(t.id)">✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'
const toast = useToast()
const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' }
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast-item {
  pointer-events: all;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  min-width: 320px;
  max-width: 440px;
  cursor: pointer;
  backdrop-filter: blur(16px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  font-size: 0.9rem;
  font-weight: 500;
  color: #f1f5f9;
}

.toast-success { background: rgba(16, 185, 129, 0.9); border: 1px solid rgba(16, 185, 129, 0.5); }
.toast-error { background: rgba(239, 68, 68, 0.9); border: 1px solid rgba(239, 68, 68, 0.5); }
.toast-warning { background: rgba(245, 158, 11, 0.9); border: 1px solid rgba(245, 158, 11, 0.5); color: #0f172a; }
.toast-info { background: rgba(59, 130, 246, 0.9); border: 1px solid rgba(59, 130, 246, 0.5); }

.toast-icon { font-size: 1.1rem; flex-shrink: 0; }
.toast-msg { flex: 1; line-height: 1.4; }
.toast-close { background: none; border: none; color: inherit; opacity: 0.7; cursor: pointer; font-size: 0.8rem; padding: 2px; flex-shrink: 0; }
.toast-close:hover { opacity: 1; }

.toast-enter-active { animation: toast-in 0.35s ease; }
.toast-leave-active { animation: toast-out 0.25s ease; }

@keyframes toast-in {
  from { opacity: 0; transform: translateX(40px) scale(0.95); }
  to { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes toast-out {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to { opacity: 0; transform: translateX(40px) scale(0.95); }
}
</style>
