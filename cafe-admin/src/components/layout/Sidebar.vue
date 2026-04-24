<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed }">
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="logo-icon">☕</div>
      <span class="logo-text" v-show="!isCollapsed">Smart Cafe</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        :class="{ active: $route.path === item.path }"
      >
        <span class="nav-icon" v-html="item.icon"></span>
        <span class="nav-label" v-show="!isCollapsed">{{ item.label }}</span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <button class="nav-item logout-btn" @click="handleLogout">
        <span class="nav-icon">⏻</span>
        <span class="nav-label" v-show="!isCollapsed">Đăng xuất</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

defineProps({
  isCollapsed: { type: Boolean, default: false }
})

const auth = useAuthStore()
const router = useRouter()

const menuItems = [
  { path: '/', label: 'Tổng quan', icon: '📊' },
  { path: '/menu', label: 'Thực đơn', icon: '🍽️' },
  { path: '/inventory', label: 'Kho hàng', icon: '📦' },
  { path: '/orders', label: 'Đơn hàng', icon: '📋' },
  { path: '/tables', label: 'Bàn & Khu vực', icon: '🪑' },
  { path: '/staff', label: 'Nhân sự', icon: '👥' },
  { path: '/reviews', label: 'Đánh giá', icon: '⭐' },
  { path: '/shifts', label: 'Ca làm', icon: '⏰' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: var(--sidebar-width);
  height: 100vh;
  background: var(--bg-sidebar);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: width var(--transition-normal);
  overflow: hidden;
}

.sidebar.collapsed {
  width: var(--sidebar-collapsed);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 20px 16px;
  border-bottom: 1px solid var(--border-color);
  min-height: 68px;
}

.logo-icon {
  font-size: 1.6rem;
  flex-shrink: 0;
  width: 32px;
  text-align: center;
}

.logo-text {
  font-family: 'Outfit', sans-serif;
  font-size: 1.2rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--accent), #fbbf24);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  white-space: nowrap;
}

.sidebar-nav {
  flex: 1;
  padding: 12px 8px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  text-decoration: none;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.nav-item:hover {
  background: var(--bg-card-hover);
  color: var(--text-primary);
}

.nav-item.active {
  background: var(--accent-light);
  color: var(--accent);
}

.nav-item.active .nav-icon {
  transform: scale(1.1);
}

.nav-icon {
  font-size: 1.15rem;
  flex-shrink: 0;
  width: 24px;
  text-align: center;
  transition: transform var(--transition-fast);
}

.nav-label {
  white-space: nowrap;
  overflow: hidden;
}

.sidebar-footer {
  padding: 8px 8px 16px;
  border-top: 1px solid var(--border-color);
}

.logout-btn {
  color: var(--error) !important;
}

.logout-btn:hover {
  background: var(--error-bg) !important;
}
</style>
