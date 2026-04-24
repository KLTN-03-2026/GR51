import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('admin_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)
  const userName = computed(() => user.value?.ho_ten || '')
  const userRole = computed(() => user.value?.vai_tro || '')

  async function login(username, password) {
    const res = await api.login({ username, password })
    const data = res.data

    if (data.success) {
      // Kiểm tra vai trò quản lý
      if (data.data.user.vai_tro !== 'quan_ly') {
        throw new Error('Tài khoản không có quyền truy cập Admin.')
      }

      token.value = data.data.token
      user.value = data.data.user
      localStorage.setItem('admin_token', data.data.token)
      localStorage.setItem('admin_user', JSON.stringify(data.data.user))
      return true
    }
    throw new Error(data.message || 'Đăng nhập thất bại')
  }

  async function logout() {
    try {
      await api.logout()
    } catch (e) {
      // ignore
    }
    token.value = null
    user.value = null
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_user')
  }

  return { token, user, isLoggedIn, userName, userRole, login, logout }
})
