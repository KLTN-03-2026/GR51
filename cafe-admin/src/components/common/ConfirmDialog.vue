<template>
  <Teleport to="body">
    <Transition name="confirm">
      <div v-if="dialog.state.show" class="confirm-overlay" @click.self="dialog.cancel()">
        <div class="confirm-box">
          <h3>{{ dialog.state.title }}</h3>
          <p>{{ dialog.state.message }}</p>
          <div class="confirm-actions">
            <button class="btn btn-ghost" @click="dialog.cancel()">Hủy</button>
            <button class="btn btn-danger" @click="dialog.accept()">Xác nhận</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useConfirm } from '@/composables/useConfirm'
const dialog = useConfirm()
</script>

<style scoped>
.confirm-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.6);
  display: flex; align-items: center; justify-content: center;
  z-index: 10000; backdrop-filter: blur(4px);
}
.confirm-box {
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: var(--radius-lg); padding: 28px;
  width: 100%; max-width: 400px; box-shadow: 0 16px 48px rgba(0,0,0,0.4);
}
.confirm-box h3 { margin-bottom: 12px; font-size: 1.1rem; color: var(--text-primary); }
.confirm-box p { color: var(--text-secondary); font-size: 0.92rem; line-height: 1.5; margin-bottom: 24px; }
.confirm-actions { display: flex; justify-content: flex-end; gap: 10px; }

.confirm-enter-active { animation: cfm-in 0.2s ease; }
.confirm-leave-active { animation: cfm-out 0.15s ease; }
@keyframes cfm-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes cfm-out { from { opacity: 1; } to { opacity: 0; } }
</style>
