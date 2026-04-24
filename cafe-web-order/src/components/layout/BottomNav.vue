<script setup>
defineProps({
  currentTab: {
    type: String,
    required: true
  },
  cartCount: {
    type: Number,
    default: 0
  }
})

defineEmits(['change-tab'])
</script>

<template>
  <nav class="bottom-nav">
    <button 
      class="nav-btn" 
      :class="{ active: currentTab === 'menu' }" 
      @click="$emit('change-tab', 'menu')"
    >
      <div class="icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
      </div>
      <span>Thực đơn</span>
    </button>
    
    <button 
      class="nav-btn" 
      :class="{ active: currentTab === 'cart' }" 
      @click="$emit('change-tab', 'cart')"
    >
      <div class="icon-wrap">
        <div class="badge" v-if="cartCount > 0">{{ cartCount }}</div>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
      </div>
      <span>Đơn hàng</span>
    </button>
  </nav>
</template>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 480px;
  background-color: var(--color-bg);
  display: flex;
  justify-content: space-around;
  padding: 12px 16px;
  box-shadow: var(--shadow-nav);
  z-index: 100;
  border-top-left-radius: 24px;
  border-top-right-radius: 24px;
}

.nav-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 1;
  padding: 12px;
  border-radius: var(--border-radius-lg);
  color: var(--color-primary);
  opacity: 0.6;
  transition: all 0.3s ease;
}

.nav-btn.active {
  background-color: var(--color-primary);
  color: var(--color-white);
  opacity: 1;
}

.icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-wrap svg {
  width: 24px;
  height: 24px;
}

.nav-btn span {
  font-weight: 500;
  font-size: 15px;
}

.badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #EF4444; /* red for badge */
  color: white;
  font-size: 10px;
  font-weight: bold;
  height: 18px;
  min-width: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--color-white);
}

.nav-btn.active .badge {
  border-color: var(--color-primary);
}
</style>
