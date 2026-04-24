import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('@/components/layout/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
      },
      {
        path: 'menu',
        name: 'menu',
        component: () => import('@/views/MenuManageView.vue'),
      },
      {
        path: 'inventory',
        name: 'inventory',
        component: () => import('@/views/InventoryView.vue'),
      },
      {
        path: 'orders',
        name: 'orders',
        component: () => import('@/views/OrdersView.vue'),
      },
      {
        path: 'tables',
        name: 'tables',
        component: () => import('@/views/TablesView.vue'),
      },
      {
        path: 'staff',
        name: 'staff',
        component: () => import('@/views/StaffView.vue'),
      },
      {
        path: 'reviews',
        name: 'reviews',
        component: () => import('@/views/ReviewsView.vue'),
      },
      {
        path: 'shifts',
        name: 'shifts',
        component: () => import('@/views/ShiftView.vue'),
      },
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth !== false && !auth.isLoggedIn) {
    return { name: 'login' }
  } else if (to.name === 'login' && auth.isLoggedIn) {
    return { name: 'dashboard' }
  }
})

export default router
