<template>
  <header class="topbar">
    <div class="topbar-left">
      <button class="toggle-btn" @click="$emit('toggle-sidebar')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <h1 class="page-title">{{ pageTitle }}</h1>
    </div>

    <div class="topbar-right">
      <div class="user-info">
        <div class="user-avatar">{{ userInitials }}</div>
        <div class="user-details">
          <span class="user-name">{{ auth.userName }}</span>
          <span class="user-role">Quản lý</span>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const auth = useAuthStore()

const pageTitles = {
  dashboard: 'Tổng quan',
  menu: 'Quản lý Thực đơn',
  inventory: 'Quản lý Kho hàng',
  orders: 'Quản lý Đơn hàng',
  tables: 'Quản lý Bàn & Khu vực',
  staff: 'Quản lý Nhân sự',
  reviews: 'Đánh giá Khách hàng',
  shifts: 'Lịch sử Ca làm',
}

const pageTitle = computed(() => pageTitles[route.name] || 'Admin')
const userInitials = computed(() => {
  const name = auth.userName || 'A'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
})
</script>

<style scoped>
.topbar {
  position: sticky;
  top: 0;
  height: var(--topbar-height);
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  z-index: 50;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.toggle-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 6px;
  border-radius: var(--radius-sm);
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
}

.toggle-btn:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.page-title {
  font-size: 1.2rem;
  font-weight: 600;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 12px 6px 6px;
  border-radius: var(--radius-lg);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent-hover));
  color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.user-details {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  font-size: 0.7rem;
  color: var(--text-muted);
}
</style>
