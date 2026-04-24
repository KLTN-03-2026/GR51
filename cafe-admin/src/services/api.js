import axios from 'axios'

const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Interceptor: tự động gắn token vào mọi request
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor: xử lý lỗi 401 (hết hạn token)
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default {
  // ====== AUTH ======
  login(payload) {
    return apiClient.post('/login', payload)
  },
  logout() {
    return apiClient.post('/logout')
  },

  // ====== DASHBOARD ======
  getDashboard() {
    return apiClient.get('/admin/dashboard')
  },

  // ====== DANH MỤC ======
  getDanhMuc() {
    return apiClient.get('/admin/danh-muc')
  },
  createDanhMuc(data) {
    return apiClient.post('/admin/danh-muc', data)
  },
  updateDanhMuc(id, data) {
    return apiClient.put(`/admin/danh-muc/${id}`, data)
  },
  deleteDanhMuc(id) {
    return apiClient.delete(`/admin/danh-muc/${id}`)
  },

  // ====== MÓN ĂN ======
  getMon(params) {
    return apiClient.get('/admin/mon', { params })
  },
  createMon(data) {
    return apiClient.post('/admin/mon', data)
  },
  updateMon(id, data) {
    return apiClient.put(`/admin/mon/${id}`, data)
  },
  deleteMon(id) {
    return apiClient.delete(`/admin/mon/${id}`)
  },

  // ====== KÍCH CỠ ======
  getKichCo() {
    return apiClient.get('/admin/kich-co')
  },
  createKichCo(data) {
    return apiClient.post('/admin/kich-co', data)
  },
  updateKichCo(id, data) {
    return apiClient.put(`/admin/kich-co/${id}`, data)
  },
  deleteKichCo(id) {
    return apiClient.delete(`/admin/kich-co/${id}`)
  },

  // ====== TOPPING ======
  getTopping() {
    return apiClient.get('/admin/topping')
  },
  createTopping(data) {
    return apiClient.post('/admin/topping', data)
  },
  updateTopping(id, data) {
    return apiClient.put(`/admin/topping/${id}`, data)
  },
  deleteTopping(id) {
    return apiClient.delete(`/admin/topping/${id}`)
  },

  // ====== CÔNG THỨC ======
  getCongThuc(maMon) {
    return apiClient.get(`/admin/cong-thuc/${maMon}`)
  },
  saveCongThuc(data) {
    return apiClient.post('/admin/cong-thuc', data)
  },

  // ====== NGUYÊN LIỆU & KHO ======
  getNguyenLieu(params) {
    return apiClient.get('/admin/nguyen-lieu', { params })
  },
  createNguyenLieu(data) {
    return apiClient.post('/admin/nguyen-lieu', data)
  },
  updateNguyenLieu(id, data) {
    return apiClient.put(`/admin/nguyen-lieu/${id}`, data)
  },
  deleteNguyenLieu(id) {
    return apiClient.delete(`/admin/nguyen-lieu/${id}`)
  },
  nhapKho(id, data) {
    return apiClient.post(`/admin/nguyen-lieu/${id}/nhap-kho`, data)
  },
  getLichSuKho(params) {
    return apiClient.get('/admin/lich-su-kho', { params })
  },

  // ====== ĐƠN HÀNG ======
  getDonHang(params) {
    return apiClient.get('/admin/don-hang', { params })
  },

  // ====== KHU VỰC ======
  getKhuVuc() {
    return apiClient.get('/admin/khu-vuc')
  },
  createKhuVuc(data) {
    return apiClient.post('/admin/khu-vuc', data)
  },
  updateKhuVuc(id, data) {
    return apiClient.put(`/admin/khu-vuc/${id}`, data)
  },
  deleteKhuVuc(id) {
    return apiClient.delete(`/admin/khu-vuc/${id}`)
  },

  // ====== BÀN ======
  getBan(params) {
    return apiClient.get('/admin/ban', { params })
  },
  createBan(data) {
    return apiClient.post('/admin/ban', data)
  },
  updateBan(id, data) {
    return apiClient.put(`/admin/ban/${id}`, data)
  },
  deleteBan(id) {
    return apiClient.delete(`/admin/ban/${id}`)
  },

  // ====== NHÂN SỰ ======
  getNhanSu(params) {
    return apiClient.get('/admin/nhan-su', { params })
  },
  createNhanSu(data) {
    return apiClient.post('/admin/nhan-su', data)
  },
  updateNhanSu(id, data) {
    return apiClient.put(`/admin/nhan-su/${id}`, data)
  },
  deleteNhanSu(id) {
    return apiClient.delete(`/admin/nhan-su/${id}`)
  },
  resetPassword(id, data) {
    return apiClient.put(`/admin/nhan-su/${id}/reset-password`, data)
  },

  // ====== ĐÁNH GIÁ ======
  getDanhGia(params) {
    return apiClient.get('/admin/danh-gia', { params })
  },

  // ====== CA LÀM ======
  getCaLam(params) {
    return apiClient.get('/admin/ca-lam', { params })
  },

  // ====== AI TRỢ LÝ ======
  aiChat(data) {
    return apiClient.post('/admin/ai-chat', data)
  },
  aiHistory(params) {
    return apiClient.get('/admin/ai-chat/history', { params })
  },
  aiClearHistory(data) {
    return apiClient.delete('/admin/ai-chat/history', { data })
  },
}
