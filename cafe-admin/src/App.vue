<template>
  <router-view v-slot="{ Component }">
    <transition name="page" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <ToastContainer />
  <ConfirmDialog />
  <AIAssistantWidget v-if="isLoggedIn" />
</template>

<script setup>
import { computed } from 'vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import AIAssistantWidget from '@/components/common/AIAssistantWidget.vue'

const isLoggedIn = computed(() => !!localStorage.getItem('admin_token'))
</script>
