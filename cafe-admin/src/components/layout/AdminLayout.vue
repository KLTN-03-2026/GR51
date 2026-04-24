<template>
  <div class="admin-layout">
    <Sidebar :isCollapsed="sidebarCollapsed" />
    <div class="main-wrapper" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <Topbar @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />
      <main class="main-content">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'

const sidebarCollapsed = ref(false)
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.main-wrapper {
  flex: 1;
  margin-left: var(--sidebar-width);
  transition: margin-left var(--transition-normal);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-wrapper.sidebar-collapsed {
  margin-left: var(--sidebar-collapsed);
}

.main-content {
  flex: 1;
  padding: 28px;
  max-width: 1400px;
  width: 100%;
}
</style>
